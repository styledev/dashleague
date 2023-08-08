<?php if ( !class_exists('dlAPI') ) {
  class dlAPI {
    function __construct( &$api ) {
      $this->user      = wp_get_current_user();
      $this->clearance = FALSE;
      
      if ( $this->user ) {
        $admin   = array('administrator');
        $allowed = array('captain', 'statitician');
        
        if ( array_intersect($admin, $this->user->roles ) ) $this->clearance = 'all';
        elseif ( array_intersect($allowed, $this->user->roles ) ) $this->clearance = 'limited';
      }
      
      $this->api($api);
    }
    
    /* API */
      public function api( &$api ) {
        $api[] = array(
          'namespace' => 'api/v1',
          'route'     => array('public', 'data'),
          'args'      => array(
            array(
              'methods'  => WP_REST_Server::READABLE,
              'callback' => array($this, 'data')
            ),
          ),
        );
        
        $api[] = array(
          'namespace' => 'api/v1',
          'route'     => array('public', 'stats'),
          'args'      => array(
            array(
              'methods'  => WP_REST_Server::READABLE,
              'callback' => array($this, 'stats')
            ),
          ),
        );
        
        $api[] = array(
          'namespace' => 'api/v1',
          'route'     => array('team', 'apply'),
          'args'      => array(
            array(
              'methods'             => WP_REST_Server::EDITABLE,
              'callback'            => array($this, 'team_apply'),
              'permission_callback' => array($this, 'api_authenticate')
            ),
          ),
        );
        
        $api[] = array(
          'namespace' => 'api/v1',
          'route'     => array('tool', 'tiers'),
          'args'      => array(
            array(
              'methods'             => WP_REST_Server::EDITABLE,
              'callback'            => array($this, 'tool_tiers_make'),
              'permission_callback' => array($this, 'api_authenticate_admin')
            ),
          ),
        );
      }
      public function api_authenticate( $request ) {
        return wp_verify_nonce($request->get_header('x_wp_nonce'), 'wp_rest');
      }
      public function api_authenticate_admin( $request ) {
        return wp_verify_nonce($request->get_header('x_wp_nonce'), 'wp_rest');
      }
      
    /* Data - Public */
      public function data() {
        $response = array('data' => FALSE);
        
        $fn = "data_{$_GET['data']}";
        $response['data'] = $this->$fn();
        
        return rest_ensure_response($response);
      }
      public function data_standings() {
        global $pxl, $wpdb;
        
        $season = isset($_GET['season']) ? $_GET['season'] : $pxl->season['number'];
        $teams = implode(',', get_posts(array(
          'post_type'      => 'team',
          'posts_per_page' => -1,
          'order'          => 'ASC',
          'orderby'        => 'title',
          'season'         => 'current',
          'fields'         => 'ids'
        )));
        
        // TODO fix 1000
        $sql = $wpdb->prepare("
          SELECT r1.name, sum(r1.mmr) +1000 as mmr, count(r1.matches) as matches, sum(r1.wins) as wins, count(r1.matches) - sum(r1.wins) as losses
          FROM (
            SELECT t1.name, sum(t1.mmr) as mmr, count(DISTINCT t1.matchID) as matches, t2.wins
            FROM dl_teams AS t1
            LEFT JOIN (
              SELECT team_id, name, matchID,
              CASE
                WHEN sum(outcome) = 2 THEN 1
                WHEN game_id = 'forfeit' THEN 1
                WHEN game_id = 'double-forfeit' THEN 0
                ELSE 0
              END as wins
              FROM dl_teams
              WHERE season = %d
              GROUP BY team_id, matchID
              ORDER BY name ASC
            ) as t2 on t1.matchID = t2.matchID AND t2.team_id = t1.team_id
            WHERE t2.team_id IN ({$teams})
            GROUP BY t1.team_id, t1.matchID
            ORDER BY name ASC
          ) as r1
          GROUP BY r1.name
          ORDER BY MMR DESC
        ", $season);
        
        $items = $wpdb->get_results($sql);
        
        return $items;
      }
      public function data_matchups() {
        global $pxl, $wpdb;
        
        $season = isset($_GET['season']) ? $_GET['season'] : $pxl->season['number'];
        $teams = implode(',', get_posts(array(
          'post_type'      => 'team',
          'posts_per_page' => -1,
          'order'          => 'ASC',
          'orderby'        => 'title',
          'season'         => 'current',
          'fields'         => 'ids'
        )));
        
        $join  = array();
        $where = '';
        
        if ( isset($_GET['cycle']) ) {
          $join[] = sprintf('JOIN dl_tiers AS tier ON tier.team_id = t.team_id AND tier.season = %d AND tier.cycle = %d', $season, $_GET['cycle']);
          $where .= ' AND (t.datetime >= tier.start AND t.datetime <= tier.end)';
        }
        
        $join_string = implode(' ', $join);
        
        $sql = $wpdb->prepare("
          SELECT t.name, group_concat(distinct t.opponent) as opponents
          FROM dl_teams t
          {$join_string}
          WHERE t.season = %d AND t.team_id IN ({$teams}) AND (t.notes IS NULL OR t.notes NOT LIKE '%s'){$where}
          GROUP BY t.name
          ORDER BY t.name ASC, t.opponent ASC
        ", $season, '%double%');
        
        $items = array();
        $_items = array_column($wpdb->get_results($sql), 'opponents', 'name');
        foreach ($_items as $team => $opponents) {
          $items[$team] = explode(',', $opponents);
        }
        
        return $items;
      }
      public function data_teams() {
        $items = array();
        
        $args = array(
          'post_type'      => 'team',
          'posts_per_page' => -1,
          'order'          => 'ASC',
          'orderby'        => 'title',
          'season'         => 'current'
        );
        
        if ( isset($_GET['team']) && !empty($_GET['team']) ) {
          $posts = new WP_Query([ 'posts_per_page' => 1, 'post_type' => 'team', 'title' => $_GET['team'] ]);
          $team  = !empty($posts->post) ? new dlTeam($posts->post) : FALSE;
          
          return $team;
        }
        else {
          $season = isset($_GET['season']) ? "season-{$_GET['season']}" : 'current';
          
          $teams = array_column(get_posts(array(
            'post_type'      => 'team',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'season' => $season,
          )), null, 'ID');
          
          foreach ($teams as $post_id => $data) {
            $team = new dlTeam($data);
            
            if ( isset($_GET['fields']) ) {
              $data = array( 'name' => $team->name );
              
              $fields = explode(',', $_GET['fields']);
              
              foreach ($fields as $field) $data[$field] = $team->$field;
            }
            
            $items[] = $data;
          }
          
          return $items;
        }
      }
      public function data_tiers( $data = array() ) {
        global $pxl, $wpdb;
        
        $params = array_merge($data, $_GET);
        $items  = array();
        
        if ( isset($params['cycle']) && !$params['cycle'] ) return FALSE;
        
        $cycles    = $wpdb->get_results($wpdb->prepare("SELECT cycle as num, start, end FROM dl_tiers WHERE season = %s GROUP BY cycle ORDER BY cycle ASC", $pxl->season['number']), ARRAY_A);
        $cycle_num = isset($params['cycle']) ? $params['cycle'] : 1;
        $cycle_key = $cycle_num - 1;
        $cycle     = $cycles[$cycle_key] ?? FALSE;
        
        if ( ! $cycle ) return FALSE;
        
        $season = isset($params['season']) ? $params['season'] : $pxl->season['number'];
        
        $teams = implode(',', get_posts(array(
          'post_type'      => 'team',
          'posts_per_page' => -1,
          'order'          => 'ASC',
          'orderby'        => 'title',
          'season'         => isset($params['season']) ? "season-{$season}": 'current',
          'fields'         => 'ids'
        )));
        
        if ( !empty($teams) ) {
          $select = array('t.name', 'tr2.cycle', 'tr2.tier as `tier`', 'tr1.tier as tier_prev');
          $join   = array();
          $where  = '';
          $order  = $cycle['num'] == 1 ? 'tr1.name ASC' : 'sr DESC';
          
          if ( isset($params['tier']) ) {
            unset($select[2]);
            $where .= $wpdb->prepare(' AND tr2.tier = %s', $params['tier']);
          }
          
          // Gets starting tier
          $join[] = $wpdb->prepare('JOIN dl_tiers AS tr1 ON t.team_id = tr1.team_id AND tr1.season = %d AND tr1.cycle = 1', $season);
          
          if ( $cycle['num'] == 1 ) {
            $select[] = "
              CASE
                WHEN tr1.tier = 'dasher' THEN 1100
                WHEN tr1.tier = 'sprinter' THEN 1050
                ELSE 1000
              END as sr
            ";
            $select[] = "
              CASE
                WHEN tr1.tier = 'dasher' THEN 1300
                WHEN tr1.tier = 'sprinter' THEN 1150
                ELSE 1000
              END as mmr
            ";
            $order = 'tr1.name ASC';
          }
          else {
            $start = DateTime::createFromFormat("Y-m-d H:i:s", "{$cycle['start']}");
            $start = $start->modify('+6 hour')->format('Y-m-d H:i:s');
            
            $select[] = "
              CASE
                WHEN tr1.tier = 'dasher' THEN SUM(t.rank_gain) + 1100
                WHEN tr1.tier = 'sprinter' THEN SUM(t.rank_gain) + 1050
                ELSE SUM(t.rank_gain) + 1000
              END as sr";
              
            $select[] = "
              CASE
                WHEN tr1.tier = 'dasher' THEN SUM(t.mmr) + 1300
                WHEN tr1.tier = 'sprinter' THEN SUM(t.mmr) + 1150
                ELSE SUM(t.mmr) + 1000
              END as mmr";
              
            $order = 'sr DESC';
            $where .= $wpdb->prepare(' AND t.datetime <= %s', $start);
          }
          
          $join[] = $wpdb->prepare('JOIN dl_tiers AS tr2 ON t.team_id = tr2.team_id AND tr2.season = %d AND tr2.cycle = %d', $season, $cycle['num']);
          
          $count         = count($select);
          $select_string = implode(', ', $select);
          $join_string   = implode("\n", $join);
          
          if ( $cycle['num'] == 1 ) {
            $sql = $wpdb->prepare("
              SELECT tr1.name, tr1.cycle, tr1.tier as `tier`, tr1.tier as tier_prev,
              CASE
                WHEN tr1.tier = 'dasher' THEN 1100
                WHEN tr1.tier = 'sprinter' THEN 1050
                ELSE 1000
              END as sr,
              CASE
                WHEN tr1.tier = 'dasher' THEN 1300
                WHEN tr1.tier = 'sprinter' THEN 1150
                ELSE 1000
              END as mmr
              FROM dl_tiers AS tr1
              WHERE tr1.season = %d AND tr1.team_id IN ({$teams}){$where}
            ", $season);
          }
          else {
            $sql = $wpdb->prepare("
              SELECT {$select_string}
              FROM dl_teams AS t
              {$join_string}
              WHERE t.season = %d AND t.team_id IN ({$teams}){$where}
              GROUP BY tr1.name, tr1.cycle, tr1.tier
              ORDER BY tr2.tier ASC, {$order}
            ", $season);
          }
          
          $_items = $wpdb->get_results($sql, ARRAY_A);
          
          if ( !empty($_items) ) {
            if ( $count == 1 ) $items = array_column($_items, 'name');
            else if ( $count == 2 ) {
              $items = array();
              
              foreach ($_items as $item) {
                $key = isset($item->tier) ? $item->tier : $item->cycle;
                if ( !isset($items[$key]) ) $items[$key] = array();
                $items[$key][] = $item->name;
              }
            }
            else {
              $items = array();
              
              foreach ($_items as $item) {
                $cycle = $item['cycle'];
                $tier  = $item['tier'];
                
                if ( !isset($items[$cycle]) ) $items[$cycle] = array();
                if ( !isset($items[$cycle][$tier]) ) $items[$cycle][$tier] = array();
                
                if ( isset($params['mmr']) ) {
                  $items[$cycle][$tier][] = array(
                    'name' => $item['name'],
                    'mmr' => $item['mmr'],
                    'sr' => $item['sr']
                  );
                }
                else $items[$cycle][$tier][] = $item['name'];
              }
              
              if ( isset($params['cycle']) ) $items = $items[$params['cycle']];
            }
          }
        }
        
        return $items;
      }
      
    /* Stats */
      public function stats() {
        $response = array('data' => FALSE);
        
        $fn = "stats_{$_GET['data']}";
        $response['data'] = $this->$fn();
        
        return rest_ensure_response($response);
      }
      public function stats_all() {
        global $pxl, $wpdb;
        
        $items = array();
        
        return $items;
      }
      
    /* Team */
      public function team_apply( $request ) {
        $response = array('data' => FALSE);
        
        $data = json_decode(file_get_contents('php://input'));
        
        update_user_meta($this->user->ID, 'dl_team', sanitize_text_field($data->team_id));
        
        return rest_ensure_response($response);
      }
      
    /* Tools */
      public function tool_cycles() {
        global $pxl, $wpdb;
        
        $cycles = $wpdb->get_results($wpdb->prepare("SELECT cycle as num, start, end FROM dl_tiers WHERE season = %d GROUP BY cycle ORDER BY cycle ASC", $pxl->season['number']), ARRAY_A);
        
        if ( empty($cycles) ) {
          $cycle_1_end = DateTime::createFromFormat('m/d/Y H:i:s', "{$pxl->season['regular_start']} 23:59:59");
          $cycle_1_end->modify('+14 day');
          $cycles = [['num' => 0, 'end' => $cycle_1_end->format('Y-m-d H:i:s')]];
        }
        
        return $cycles;
      }
      public function tool_tiers( $cycle, $current ) {
        global $pxl, $wpdb;
        
        $cycle_end = DateTime::createFromFormat('Y-m-d H:i:s', $cycle['end']);
        $cycle_end->add(new DateInterval('P1D'));
        
        $teams = get_posts(array(
          'order'          => 'ASC',
          'orderby'        => 'post_title',
          'posts_per_page' => -1,
          'post_type'      => 'team',
          'season'         => 'current',
        ));
        
        $teams = !empty($teams) ? array_column($teams, 'post_title', 'ID') : FALSE;
        $teams_ids = $teams ? implode(', ', array_keys($teams)) : [];
        
        $breakdown = [
          'dasher'   => isset($current['dasher']) ? count($current['dasher']) : 0,
          'sprinter' => isset($current['sprinter']) ? count($current['sprinter']) : 0,
          'walker'   => isset($current['walker']) ? count($current['walker']) : 0,
        ];
        
        if ( count($teams)%3 === 0 ) {
          $even = count($teams)/3;
          $breakdown = [
            'dasher'   => $even,
            'sprinter' => $even,
            'walker'   => $even,
          ];
        }
        
        $order = $cycle['num'] == 6 ? 'ORDER BY sr DESC' : 'ORDER BY mmr DESC';
        
        $teams_sql = $wpdb->prepare(
          'SELECT tm.name, tm.team_id,
            CASE 
              WHEN tr.tier = "dasher" THEN sum(tm.mmr) + 1300
              WHEN tr.tier = "sprinter" THEN sum(tm.mmr) + 1150
              ELSE sum(tm.mmr) + 1000
            END as mmr,
            CASE 
              WHEN tr.tier = "dasher" THEN sum(tm.rank_gain) + 1100
              WHEN tr.tier = "sprinter" THEN sum(tm.rank_gain) + 1050
              ELSE sum(tm.rank_gain) + 1000
            END as sr
           FROM dl_teams AS tm
           JOIN dl_tiers AS tr ON tm.team_id = tr.team_id AND tr.season = %d AND tr.cycle = 1
           WHERE tm.season = %d AND tm.datetime <= "%s" AND tm.team_id IN ('.$teams_ids.')
           GROUP BY tm.name
           '.$order.'
          ',
          $pxl->season['number'],
          $pxl->season['number'],
          $cycle_end->format('Y-m-d H:i:s')
        );
        
        $teams = $wpdb->get_results($teams_sql, ARRAY_A);
        
        $tiers = [];
        if ( !empty($teams) ) {
          
          $dasher = array_slice($teams, 0, $breakdown['dasher']);
          // usort($dasher, fn($a, $b) => $b['sr'] <=> $a['sr']);
          $tiers[] = $dasher;
          
          $sprinter = array_slice($teams, $breakdown['dasher'], $breakdown['sprinter']);
          // usort($sprinter, fn($a, $b) => $b['sr'] <=> $a['sr']);
          $tiers[] = $sprinter;
          
          $walker = array_slice($teams, $breakdown['dasher']+$breakdown['sprinter'], $breakdown['walker']);
          // usort($walker, fn($a, $b) => $b['sr'] <=> $a['sr']);
          $tiers[] = $walker;
        }
        
        return $tiers;
      }
      public function tool_tiers_make( $data = array() ) {
        global $pxl, $wpdb;
        
        $response = array('status' => 'success');
        
        $dates = array(
          'start' => isset($_POST['dateStart']) ? "{$_POST['dateStart']} 00:00:00" : NULL,
          'end'   => isset($_POST['dateEnd']) ? "{$_POST['dateEnd']} 23:59:59" : NULL,
        );
        
        if ( isset($_POST['tiers']) ) {
          $tiers  = $_POST['tiers'];
          $cycles = [[ 'num' => 0 ]];
          $cycle  = array_pop($cycles);
        }
        else {
          $pxl->season_dates();
          
          $cycles  = $pxl->api->tool_cycles();
          $cycle   = $cycles[array_key_last($cycles)];
          $current = $pxl->api->data_tiers(array('cycle' => $cycle['num'], 'mmr' => TRUE));
          $tiers   = $pxl->api->tool_tiers($cycle, $current);
        }
        
        foreach ($tiers as $tier => $teams) {
          switch ($tier) {
            case 0: $tier  = 'dasher'; break;
            case 1: $tier  = 'sprinter'; break;
            default: $tier = 'walker'; break;
          }
          
          foreach ($teams as $team) {
            if ( is_array($team) ) {
              unset($team['mmr']);
              unset($team['sr']);
            }
            else {
              $team = explode('|', $team);
              $team = ['team_id' => $team[1], 'name' => $team[0]];
            }
            
            $data = array_merge($team, $dates, array(
              'tier'   => $tier,
              'cycle'  => $cycle['num'] + 1,
              'season' => $pxl->season['number'],
            ));
            
            $wpdb->insert('dl_tiers', $data);
          }
        }
        
        return rest_ensure_response($response);
      }
  }
}