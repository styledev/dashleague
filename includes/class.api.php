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
              'callback'            => array($this, 'tool_tiers'),
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
        
        $sql = $wpdb->prepare("
          SELECT r1.name, sum(r1.rank_gain) as mmr, count(r1.matches) as matches, sum(r1.wins) as wins, count(r1.matches) - sum(r1.wins) as losses
          FROM (
            SELECT t1.name, sum(t1.rank_gain) as rank_gain, count(DISTINCT t1.matchID) as matches, t2.wins
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
          WHERE t.season = %d AND t.team_id IN ({$teams}){$where}
          GROUP BY t.name
          ORDER BY t.name ASC, t.opponent ASC
        ", $season);
        
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
          $post = get_page_by_title($_GET['team'], OBJECT, 'team');
          
          $team = $post ? new dlTeam($post) : FALSE;
          
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
            if ( isset($_GET['fields']) ) {
              $team   = array();
              $fields = explode(',', $_GET['fields']);
              
              foreach ($fields as $field) {
                $team[$field] = $data->$field;
              }
            }
            else {
              $team = new dlTeam($data);
            }
            
            $items[] = $team;
          }
          
          return $items;
        }
      }
      public function data_tiers( $data = array() ) {
        global $pxl, $wpdb;
        
        $params = array_merge($data, $_GET);
        
        $items  = array();
        $cycle  = isset($params['cycle']) ? $params['cycle'] : 1;
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
          $select = array('t.name', 't.cycle', 't.tier');
          $join   = array();
          $where  = '';
          $order  = 't.name ASC';
          
          if ( isset($params['tier']) ) {
            unset($select[2]);
            $where .= $wpdb->prepare(' AND t.tier = %s', $params['tier']);
          }
          
          if ( $cycle != 1 ) {
            $join[] = $wpdb->prepare('JOIN dl_teams AS tm ON tm.team_id = t.team_id AND tm.season = %d', $season);
            
            if ( isset($params['mmr']) ) {
              $select[] = 'SUM(tm.rank_gain) + 1000 as mmr';
              $order    = "mmr DESC, $order";
            }
            else {
              $select[] = 'SUM(tm.rank_gain) as `rank`';
              $order    = "`rank` DESC, $order";
            }
          }
          else $select[] = '1000 as mmr';
          
          if ( $cycle && $cycle != 1 ) {
            $where .= $wpdb->prepare(' AND t.cycle = %d AND tm.datetime <= t.end', $params['cycle']);
          }
          
          $count         = count($select);
          $select_string = implode(', ', $select);
          $join_string   = implode(' ', $join);
          
          $sql = $wpdb->prepare("
            SELECT {$select_string}
            FROM dl_tiers AS t
            {$join_string}
            WHERE t.season = %d AND t.team_id IN ({$teams}){$where}
            GROUP BY t.name, t.cycle, t.tier
            ORDER BY t.cycle ASC, t.tier ASC, {$order}
          ", $season);
          
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
            else if ( in_array('SUM(tm.rank_gain) as mmr', $select) ) {
              $items = array();
              
              foreach ($_items as $item) {
                $tier = $item['tier'];
                
                if ( !isset($items[$tier]) ) $items[$tier] = array();
                
                $items[$tier][] = $item;
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
                    'mmr' => $item['mmr']
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
      public function tool_tiers( $data = array() ) {
        global $pxl, $wpdb;
        
        $response = array('status' => 'success');
        
        $dates = array(
          'start' => "{$_POST['dateStart']} 00:00:00",
          'end'   => "{$_POST['dateEnd']} 11:59:59",
        );
        
        $pxl->season_dates();
        
        $teams = get_posts(array(
          'fields'         => 'ids',
          'posts_per_page' => -1,
          'post_type'      => 'team',
          'season'         => 'current',
        ));
        
        if ( !empty($teams) ) $teams = implode(', ', $teams);
        
        $cycles = $wpdb->get_results($wpdb->prepare("SELECT cycle, start, end FROM dl_tiers WHERE season = 4 GROUP BY cycle ORDER BY cycle ASC", $pxl->season['number']));
        $cycle  = array_pop($cycles);
        
        $cylce_teams   = $wpdb->prepare("SELECT name, team_id, sum(rank_gain) + 1000 as mmr FROM dl_teams WHERE season = %d AND datetime <= '%s' AND team_id IN ({$teams}) GROUP BY name ORDER BY mmr DESC", $pxl->season['number'], $cycle->end);
        $cycle_results = $wpdb->get_results($cylce_teams, ARRAY_A);
        $tiers = array_chunk($cycle_results, 11);
        
        if ( count($tiers[2]) < count($tiers[1]) ) {
          $diff   = ROUND((count($tiers[1]) - count($tiers[2])) / 2, 0);
          $offset = count($tiers[1]) - $diff;
          $move   = array_splice($tiers[1], $offset, $diff);
          
          $tiers[2] = array_merge($move, $tiers[2]);
        }
        
        if ( count($tiers[1]) < count($tiers[0]) ) {
          $diff   = ROUND((count($tiers[0]) - count($tiers[1])) / 2, 0);
          $offset = count($tiers[0]) - $diff;
          $move   = array_splice($tiers[0], $offset, $diff);
          
          $tiers[1] = array_merge($move, $tiers[1]);
        }
        
        foreach ($tiers as $tier => $teams) {
          switch ($tier) {
            case 0: $tier = 'dasher'; break;
            case 1: $tier = 'sprinter'; break;
            default: $tier = 'walker'; break;
          }
          
          foreach ($teams as $team) {
            unset($team['mmr']);
            
            $data = array_merge($team, $dates, array(
              'tier'   => $tier,
              'cycle'  => $cycle->cycle + 1,
              'season' => $pxl->season['number'],
            ));
            
            $wpdb->insert('dl_tiers', $data);
          }
        }
        
        return rest_ensure_response($response);
      }
  }
}