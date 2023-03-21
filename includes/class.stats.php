<?php if ( !class_exists('dlStats') ) {
  class dlStats {
    public $internal = FALSE;
    function __construct( &$api ) {
      $this->url       = 'https://backend-hyperdash.be/current/index.php';
      $this->user      = wp_get_current_user();
      $this->clearance = FALSE;
      
      if ( $this->user ) {
        $admin   = array('administrator');
        $allowed = array(
          // 'captain', 'statitician'
        );
        
        if ( array_intersect($admin, $this->user->roles ) ) $this->clearance = 'all';
        elseif ( array_intersect($allowed, $this->user->roles ) ) $this->clearance = 'limited';
      }
      
      $this->actions();
      $this->stats_api($api);
    }
    
    // Hooks: Actionss
      public function actions() {}
      
    // API: Dash League Stats
      public function stats_api( &$api ) {
        $api[] = array(
          'namespace' => 'api/v1',
          'route'     => array('stats', 'game'),
          'args'      => array(
            array(
              'methods'             => WP_REST_Server::CREATABLE,
              'callback'            => array($this, 'stats_game'),
              // 'permission_callback' => array($this, 'api_authenticate')
            ),
          ),
        );
        
        $api[] = array(
          'namespace' => 'api/v1',
          'route'     => array('stats', 'data'),
          'args'      => array(
            array(
              'methods'             => WP_REST_Server::READABLE,
              'callback'            => array($this, 'data')
            ),
          ),
        );
        
        $api[] = array(
          'namespace' => 'api/v1',
          'route'     => array('stats', 'forfeit'),
          'args'      => array(
            array(
              'methods'             => WP_REST_Server::CREATABLE,
              'callback'            => array($this, 'stats_forfeit'),
              'permission_callback' => array($this, 'api_authenticate')
            ),
          ),
        );
        
        $api[] = array(
          'namespace' => 'api/v1',
          'route'     => array('stats', 'gamer'),
          'args'      => array(
            array(
              'methods'             => WP_REST_Server::CREATABLE,
              'callback'            => array($this, 'stats_gamer'),
              'permission_callback' => array($this, 'api_authenticate')
            ),
          ),
        );
        
        $api[] = array(
          'namespace' => 'api/v1',
          'route'     => array('stats', 'match'),
          'args'      => array(
            array(
              'methods'             => WP_REST_Server::ALLMETHODS,
              'callback'            => array($this, 'stats_match'),
              'permission_callback' => array($this, 'api_authenticate')
            ),
          ),
        );
        
      }
      public function stats_forfeit() {
        global $pxl, $wpdb;
        
        $response = array();
        
        $teams = array(
          'forfeit' => array(
            'team'    => get_post($_POST['team_forfeit']),
            'players' => get_field('players', $_POST['team_forfeit']),
          ),
          'opponent' => array(
            'team'    => get_post($_POST['team_opponent']),
            'players' => get_field('players', $_POST['team_opponent']),
          )
        );
        
        $teams['forfeit']['vs']  = $teams['opponent']['team'];
        $teams['opponent']['vs'] = $teams['forfeit']['team'];
        
        $forfeit_type = $_POST['forfeit_type'];
        $matchID      = in_array($forfeit_type, array('match', 'double-forfeit')) ? "{$_POST['forfeit_date']}={$teams['forfeit']['team']->post_title}<>{$teams['opponent']['team']->post_title}" : $forfeit_type;
        $datetime     = DateTime::createFromFormat("Ymd H:i", "{$_POST['forfeit_date']} 23:59");
        
        switch ($forfeit_type) {
          case 'match':
            $game_id = 'forfeit';
          break;
          case 'double-forfeit':
            $game_id = 'double-forfeit';
          break;
          default:
            $game_id = 'map-forfeit';
          break;
        }
        
        $data = array(
          'datetime' => $datetime->format('Y-m-d H:i:s'),
          'game_id'  => $game_id,
          'matchID'  => $matchID,
          'season'   => $pxl->season['number'],
        );
        
        switch ($forfeit_type) {
          case 'match':
            $notes = "{$teams['forfeit']['team']->post_title} forfeits the match to {$teams['opponent']['team']->post_title}";
            $winner = 'Red';
          break;
          case 'double-forfeit':
            $notes = "Double forfeit for {$teams['forfeit']['team']->post_title} and {$teams['opponent']['team']->post_title}";
            $winner = FALSE;
          break;
          default:
            $notes = "{$teams['forfeit']['team']->post_title} forfeits a map to {$teams['opponent']['team']->post_title}";
            $winner = 'Red';
          break;
        }
        
        $dl_game_stats = array(
          'game_id'      => $game_id,
          'matchID'      => $matchID,
          'datetime'     => $datetime->format('Y-m-d H:i:s'),
          'winner'       => $winner,
          'red_players'  => $teams['opponent']['team']->post_title,
          'blue_players' => $teams['forfeit']['team']->post_title,
          'notes'        => $notes,
        );
        
        $wpdb->insert('dl_game_stats', $dl_game_stats);
        
        return rest_ensure_response($response);
      }
      public function stats_game() {
        $data     = json_decode(file_get_contents('php://input'));
        $response = array('stats' => FALSE);
        
        if ( isset($data->game_id) ) $response['stats'] = $this->dl_game_stats($data->game_id);
        
        return rest_ensure_response($response);
      }
      public function stats_gamer() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $data     = json_decode(file_get_contents('php://input'));
        $response = array();
        
        $current = sprintf('"name":"%s","tag":"%s"', $_POST['name_current'], $_POST['tag']);
        $replace = sprintf('"name":"%s","tag":"%s"', $_POST['name_corrected'], $_POST['tag']);
        
        $sql = sprintf('
          UPDATE dl_game_stats SET red_players = replace(red_players, \'%1$s\', \'%2$s\'), blue_players = replace(blue_players, \'%1$s\', \'%2$s\'), dropped_players = replace(dropped_players, \'%1$s\', \'%2$s\');',
          $current, $replace
        );
        
        $result = $wpdb->query($sql);
        
        if ( !$result ) $response['error'] = 'Name change failed.';
        
        return rest_ensure_response($response);
      }
      public function stats_games() {
        $fields   = array_filter($_GET);
        $response = array('status' => 'success');
        
        if ( isset($fields['game_ids']) ) {
          $game_ids = explode(',', (str_replace(' ', '', $fields['game_ids'])));
          $date     = str_replace('-', '', $fields['range_start']);
          $matchID  = "{$date}={$fields['clan_a']}<>{$fields['clan_b']}";
          
          foreach ($game_ids as $game_id) {
            $data = $this->dl_game_stats($game_id, $matchID);
          }
        }
        else {
          $response['status'] = 'error';
          $response['error']  = 'All fields are required';
        }
        
        return rest_ensure_response($response);
      }
      public function stats_match() {
        global $wpdb;
        
        $response = array('status' => 'success');
        
        if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
          $response['match'] = FALSE;
          
          $fields = array_filter($_GET);
          
          if ( isset($fields['game_ids']) ) $response = $this->stats_games();
          else if ( count($fields) >= 3 ) {
            $data = $this->dl_game_ids($fields);
            
            if ( is_array($data) ) $response['match'] = $data;
            else {
              $response['error'] = $data;
              $response['status'] = false;
            }
          }
          else {
            $response['error'] = 'All fields are required';
          }
        }
        else if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
          include('class.mmr.php');
          
          $this->maps();
          
          $mmr     = new dlMMR();
          $matchID = isset($_POST['matchID']) ? $_POST['matchID'] : FALSE;
          
          if ( $matchID ) {
            $data    = array();
            $matches = $this->games(array('where' => "matchID = '{$matchID}'"));
            $match   = !empty($matches) ? $matches[$matchID] : FALSE;
            
            if ( $match ) {
              foreach ($match['games'] as $game) $data[] = $this->stats_to_data($matchID, $game, $match);
            }
            
            $mmr_gain = $mmr->match('MMR', $data);
            $mmr->match('SR', $data, $mmr_gain);
            
            $wpdb->query($wpdb->prepare("UPDATE dl_game_stats SET recorded = CURRENT_TIMESTAMP WHERE matchID = '%s'", $matchID));
          }
          else {
            fns::error('NO MATCH ID then get $mmr->match($_POST);');
            // $response['ranks'] = $mmr->match($_POST);
          }
        }
        else if ( $_SERVER['REQUEST_METHOD'] == 'PUT' ) {
          $data = (array)json_decode(file_get_contents('php://input'), TRUE);
          
          if ( !empty($data['info']['map_id']) ) {
            $map    = get_post($data['info']['map_id']);
            $map_hd = get_field('map_hd', $data['info']['map_id']);
            $type   = get_field('mode', $data['info']['map_id']);
            
            if ( $type == 'control-point' ) $type = 'ControlPoint';
            
            $date = DateTime::createFromFormat("F j Y H:i", "{$data['info']['date']['month']} {$data['info']['date']['day']} {$data['info']['date']['year']} 12:00");
            
            $teams      = [];
            $winner     = FALSE;
            $round_data = [];
            
            foreach ($data['teams'] as $key => $team) {
              $color = $key === 0 ? 'Red' : 'Blue';
              if ( !$winner && $team['outcome'] ) $winner = $color;
              
              if ( $_team = get_post($team['team_id']) ) {
                $players = array_values(array_filter($team['players']));
              
                $teams[$color] = [
                  'name'    => $_team->post_title,
                  'players' => []
                ];
                
                switch ($type) {
                  case 'ControlPoint':
                  case 'domination':
                    $teams[$color]['score'] = $team['score_points'];
                    break;
                  case 'payload':
                    $teams[$color]['score'] = (int)$team['score_percentage'] * 100;
                    $time = explode(':', $team['score_time']);
                    
                    $round_data[] = [
                      'round_time' => ($time[0] * 60) + $time[1],
                      'team'       => strtolower($_team->post_title)
                    ];
                    break;
                  default:break;
                }
                
                foreach ($players as $key => $player) {
                  if ( empty($player['player_id']) ) continue;
                  
                  $_player = get_post($player['player_id']);
                  
                  $teams[$color]['players'][] = [
                    'id'        => get_field('gamer_id', $player['player_id']),
                    'name'      => $_player->post_title,
                    'tag'       => $_team->post_title,
                    'kills'     => $player['kills'],
                    'deaths'    => $player['deaths'],
                    'score'     => $player['score'],
                  ];
                }
              }
              else {
                $response['status'] = 'error';
                $response['error'] = 'Specify Teams';
                return rest_ensure_response($response);
              }
            }
            
            global $wpdb;
            
            if ( isset($teams['Red']['score']) && isset($teams['Blue']['score']) ) {
              $stats = [
                'game_id'         => NULL,
                'matchID'         => sprintf('%s=%s<>%s', $date->format('Ymd'), $teams['Red']['name'], $teams['Blue']['name']),
                'datetime'        => $date->format('Y-m-d H:i'),
                'approved'        => NULL,
                'recorded'        => NULL,
                'type'            => ucwords($type),
                'map'             => $map_hd[0],
                'time'            => sprintf("00:%s", $data['info']['time']),
                'winner'          => $winner,
                'red_score'       => $teams['Red']['score'],
                'red_players'     => json_encode($teams['Red']['players'], JSON_UNESCAPED_UNICODE),
                'blue_score'      => $teams['Blue']['score'],
                'blue_players'    => json_encode($teams['Blue']['players'], JSON_UNESCAPED_UNICODE),
                'dropped_players' => NULL,
                'round_data'      => empty($round_data) ? NULL : json_encode($round_data, JSON_UNESCAPED_UNICODE),
                'notes'           => 'Manually Added',
              ];
              
              $result = $wpdb->insert('dl_game_stats', $stats);
              
              if ( !$result ) {
                $response['status'] = 'error';
                $response['error'] = 'Failed to Add';
                return rest_ensure_response($response);
              }
            }
            else {
              $response['status'] = 'error';
              $response['error'] = 'Missing Scores';
              return rest_ensure_response($response);
            }
          }
          else {
            $response['status'] = 'error';
            $response['error'] = 'Missing Map';
            return rest_ensure_response($response);
          }
        }
        
        return rest_ensure_response($response);
      }
      public function player_ids( $ids ) {
        global $wpdb;
        
        $in_str_arr = array_fill(0, count($ids), '%s');
        $in_str     = join(',', $in_str_arr);
        
        $players = $wpdb->get_results($wpdb->prepare("
            SELECT pm.post_id as id, p.post_title as player, group_concat(pm.meta_value) as gamer_ids
            FROM {$wpdb->prefix}postmeta AS pm
            JOIN {$wpdb->prefix}posts AS p ON p.id = pm.post_id
            WHERE pm.meta_key LIKE 'gamer_ids_%' and pm.meta_value IN ({$in_str})
            GROUP BY pm.post_id
          ", $ids
        ), ARRAY_A);
        
        if ( $players ) {
          if ( count($players) > 1 ) return $players;
          else {
            $player = $players[0];
            $player['gamer_ids'] = empty($player['gamer_ids']) ? array() : explode(',', $player['gamer_ids']);
            
            return $player;
          }
        }
        
        return FALSE;
      }
      private function maps() {
        $this->maps = array();
        
        $map_ids = get_posts(array(
          'fields'         => 'ids',
          'post_type'      => 'map',
          'posts_per_page' => -1,
        ));
        
        foreach ($map_ids as $map_id) {
          $map_hd = get_field('map_hd', $map_id);
          if ( $map_hd ) {
            foreach ($map_hd as $map) $this->maps[$map] = $map_id;
          }
        }
      }
      private function player_id( $player ) {
        global $wpdb;
        
        $sql = $wpdb->prepare("
          SELECT p.id
          FROM {$wpdb->prefix}postmeta AS pm
          JOIN {$wpdb->prefix}posts AS p ON p.id = pm.post_id
          WHERE p.post_title = '%s' AND pm.meta_value = '%s'
        ", $player['name'], $player['id']);
        
        return $wpdb->get_var($sql);
      }
      private function stats_to_data( $matchID, $game, $match ) {
        global $pxl, $wpdb;
        
        $data = array(
          'info' => array(
            'datetime' => $game['datetime'],
            'game_id'  => $game['game_id'],
            'map_id'   => $game['map'] ? $this->maps[$game['map']] : NULL,
            'map'      => $game['map'],
            'matchID'  => $matchID,
            'time'     => $game['time'],
            'type'     => strtolower($game['type']),
          ),
          'teams' => array()
        );
        
        if ( in_array($game['game_id'], array('forfeit', 'map-forfeit')) ) {
          $data['winner'] = $match['teams'][$game['winner']]['id'];
        }
        
        foreach ($game['teams'] as $team_name => $args) {
          $team = array(
            'team_id' => $match['teams'][$team_name]['id'],
            'outcome' => $game['winner'] == $team_name ? 1 : 0,
          );
          
          if ( !empty($game['type']) ) {
            if ( $game['type'] == 'Payload' ) {
              $team['score_percentage'] = str_replace('%', '', $args['score']);
              $team['score_time']       = !empty($game['round_data']) && isset($game['round_data'][$team_name]) ? gmdate('H:i:s', $game['round_data'][$team_name]) : NULL;
              $team['players']          = array();
            }
            else $team['score_points'] = $args['score'];
          }
          
          if ( is_array($args['players']) ) {
            foreach ($args['players'] as $player) {
              $player = (array)$player;
              
              if ( $id = $this->player_id($player) ) {
                $player['player_id'] = $id;
                $player['gamer_id']  = $player['id'];
                $player['team']      = $player['tag'];
                
                unset($player['id']); unset($player['tag']);
                
                $team['players'][] = $player;
              }
              else throw new Exception("[{$player['tag']}] {$player['name']}: no `player_id` found for gamer_id {$player['id']}.");
            }
          }
          
          $data['teams'][$team['team_id']] = $team;
          
          rsort($data['teams']);
          
          $data['teams'] = array_values($data['teams']);
        }
        
        // Sort by Loser first to account for Forfeits
          fns::array_sortBy('outcome', $data['teams']);
        
        return $data;
      }
      
    // API: Data
      public function data() {
        $response = array('data' => FALSE);
        
        $fn = "data_{$_GET['data']}";
        $response['data'] = $this->$fn();
        
        return rest_ensure_response($response);
      }
      public function data_standings() {
        global $pxl, $wpdb;
        
        $items  = array();
        $season = isset($_GET['season']) ? $_GET['season'] : $pxl->season['number'];
        $teams  = implode(',', get_posts(array(
          'post_type'      => 'team',
          'posts_per_page' => -1,
          'order'          => 'ASC',
          'orderby'        => 'title',
          'season'         => 'current',
          'fields'         => 'ids'
        )));
        
        if ( !empty($teams) ) {
          $cycles = $wpdb->get_results($wpdb->prepare("SELECT cycle as num, start, end FROM dl_tiers WHERE season = %s GROUP BY cycle ORDER BY cycle ASC", $pxl->season['number']), ARRAY_A);
          $start = $cycles[0];
          
          $sql = $wpdb->prepare("
            SELECT r1.name, count(r1.matches) as Matches, sum(r1.wins) as wins,
              CASE 
                WHEN tr.tier = 'dasher' THEN sum(r1.mmr) + 1200
                WHEN tr.tier = 'sprinter' THEN sum(r1.mmr) + 1100
                ELSE sum(r1.mmr) + 1000
              END as mmr,
              sum(r1.sr) + 1000 as sr
            FROM (
              SELECT t1.name, t1.team_id, sum(t1.mmr) as mmr, sum(t1.rank_gain) as sr, count(DISTINCT t1.matchID) as matches, t2.wins
              FROM dl_teams AS t1
              LEFT JOIN (
                SELECT team_id, name, matchID,
                CASE
                  WHEN sum(outcome) = 2 THEN 1
                  WHEN game_id = 'forfeit' THEN 1
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
            JOIN dl_tiers AS tr ON r1.team_id = tr.team_id AND tr.start = %s AND tr.end = %s
            GROUP BY r1.name
            ORDER BY SR DESC, MMR DESC
          ", $season, $start['start'], $start['end']);
          
          $items = $wpdb->get_results($sql);
        }
        
        return $items;
      }
      public function data_stats() {
        global $pxl, $wpdb;
        
        $query = array(
          'join' => array(),
          'select' => array(),
          'where' => array()
        );
        
        if ( isset($_GET['cycle']) ) {
          $query['join'][] = sprintf('JOIN dl_tiers AS tier ON tier.team_id = t.team_id AND tier.season = %d AND tier.cycle = %d', $season, $_GET['cycle']);
          $where[] = ' AND (t.datetime >= tier.start AND t.datetime <= tier.end)';
        }
        
        $season = isset($_GET['season']) ? $_GET['season'] : $pxl->season['number'];
        
        $items = $this->stats($season, $query);
        
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
      public function data_tiers( $data = array() ) {
        global $pxl, $wpdb;
        fns::put('contact styledev and tell him this link');die;
        $params = array_merge($data, $_GET);
        
        $season = isset($params['season']) ? $params['season'] : $pxl->season['number'];
        $teams = implode(',', get_posts(array(
          'post_type'      => 'team',
          'posts_per_page' => -1,
          'order'          => 'ASC',
          'orderby'        => 'title',
          'season'         => 'current',
          'fields'         => 'ids'
        )));
        
        $select = array('t.name', 't.cycle', 't.tier');
        $join   = array();
        $where  = '';
        $order  = 't.name ASC';
        
        if ( isset($params['tier']) ) {
          unset($select[2]);
          $where .= $wpdb->prepare(' AND t.tier = %s', $params['tier']);
        }
        
        if ( isset($params['mmr']) ) {
          $select[] = 'SUM(tm.mmr) as mmr';
          $join[]   = $wpdb->prepare('JOIN dl_teams AS tm ON tm.team_id = t.team_id AND tm.season = %d', $season);
          $order    = "mmr DESC, $order";
        }
        else {
          $join[]   = $wpdb->prepare('JOIN dl_teams AS tm ON tm.team_id = t.team_id AND tm.season = %d', $season);
          $order    = "SUM(tm.mmr) DESC, $order";
        }
        
        if ( isset($params['cycle']) ) {
          unset($select[1]);
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
        
        $_items = $wpdb->get_results($sql);
        
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
          else if ( in_array('SUM(tm.mmr) as mmr', $select) ) {
            $items = array();
            
            foreach ($_items as $item) {
              $tier = $item->tier;
              
              if ( !isset($items[$tier]) ) $items[$tier] = array();
              
              $items[$tier][] = $item;
            }
          }
          else {
            $items = array();
            
            foreach ($_items as $item) {
              $cycle = $item->cycle;
              $tier = $item->tier;
              
              if ( !isset($items[$cycle]) ) $items[$cycle] = array();
              if ( !isset($items[$cycle][$tier]) ) $items[$cycle][$tier] = array();
              
              $items[$cycle][$tier][] = $item->name;
            }
          }
        }
        else $items = array();
        
        return $items;
      }
      
    // API: HD
      public function dl_game_stats( $game_id, $matchID = NULL ) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare("SELECT * FROM dl_game_stats WHERE game_id = '%s'", $game_id), ARRAY_A);
        
        // Call API if not cached
          if ( !$stats ) {
            $data = $this->api_call('game_stats/get', array('game_id' => $game_id));
            
            $stats = array(
              'game_id'         => $game_id,
              'matchID'         => $matchID,
              'datetime'        => $data->info->datetime,
              'type'            => $data->info->type,
              'map'             => $data->info->map,
              'time'            => $data->info->time,
              'winner'          => $data->info->winner,
              'blue_score'      => $data->blue->score,
              'blue_players'    => json_encode($data->blue->players, JSON_UNESCAPED_UNICODE),
              'red_score'       => $data->red->score,
              'red_players'     => json_encode($data->red->players, JSON_UNESCAPED_UNICODE),
              'dropped_players' => isset($data->dropped_players) ? json_encode($data->dropped_players, JSON_UNESCAPED_UNICODE) : NULL,
              'round_data'      => isset($data->round_data) ? json_encode($data->round_data, JSON_UNESCAPED_UNICODE) : NULL,
            );
            
            if ( $wpdb->insert('dl_game_stats', $stats) ) $stats['id'] = $wpdb->insert_id;
          }
          
        // Need to strip player game ids before sending back to interface.
          $stats['red_players']     = $this->players_remove_gamer_id_alt($stats['red_players']);
          $stats['blue_players']    = $this->players_remove_gamer_id_alt($stats['blue_players']);
          $stats['dropped_players'] = $this->players_remove_gamer_id_alt($stats['dropped_players']);
          
        // Decode
          $stats['round_data'] = json_decode($stats['round_data']);
          
        return $stats;
      }
      public function dl_game_ids( $params = array() ) {
        global $wpdb;
        
        $date    = str_replace('-', '', $params['range_start']);
        $matchID = "{$date}={$params['clan_a']}<>{$params['clan_b']}";
        $match   = $wpdb->get_results($wpdb->prepare("SELECT * FROM dl_game_stats WHERE matchID = '%s'", $matchID), ARRAY_A);
        
        if ( !$match ) {
          $range_start = DateTime::createFromFormat("Y-m-d", "{$params['range_start']}");
          $params['range_end'] = $range_start->modify('+1 day')->format('Y-m-d');
          
          $data = $this->api_call('game_stats/get_game_id', $params);
          
          if ( is_array($data) ) {
            if ( empty($data) ) return 'No Results Found - Adjusting the date and try again.';
            
            $match = array();
            
            foreach ($data as $key => $d) {
              $stats = $this->dl_game_stats($d->GameId, $matchID);
              $match[] = $stats;
            }
          }
          else return $data;
        }
        else {
          foreach ($match as $key => $m) {
            $match[$key]['red_players']     = $this->players_remove_gamer_id_alt($m['red_players']);
            $match[$key]['blue_players']    = $this->players_remove_gamer_id_alt($m['blue_players']);
            $match[$key]['dropped_players'] = $this->players_remove_gamer_id_alt($m['dropped_players']);
            $match[$key]['round_data']      = json_decode($m['round_data']);
          }
        }
        
        return $match;
      }
      
    // API: Helpers
      public function api_authenticate( $request ) {
        if ( $this->clearance ) return TRUE;
        
        return FALSE;
      }
      public function api_call( $endpoint, $params = array() ) {
        $query    = implode(',', $params);
        $response = wp_remote_get($this->url . "/{$endpoint}?" . http_build_query($params), array(
          'headers' => array(
            'x-api-token' => HD_API_TOKEN,
            'x-api-auth'  => HD_ID . ':' . bin2hex(hash('sha256', HD_API_TOKEN . ".{$query}." . HD_SECRET, true)),
          ),
          'timeout'   => 30,
          'sslverify' => FALSE
        ));
        
        if ( !is_wp_error($response) ) {
          if ( 200 == wp_remote_retrieve_response_code($response) ) {
            $body = json_decode(wp_remote_retrieve_body($response));
            
            if ( isset($body->ErrorMessage) ) return $body->ErrorMessage;
            else return $body->Data;
          }
          else return wp_remote_retrieve_response_message($response);
        }
        else return $response->get_error_message();
      }
      private function players_remove_gamer_id_alt( $players ) {
        if ( !$players ) return $players;
        
        $players = json_decode($players);
        
        if ( $this->clearance !== 'all' ) {
          foreach ($players as $key => $player) unset($players[$key]->id);
        }
        
        return $players;
      }
      
    // Matches
      public function games( $options = array() ) {
        $games_stats = $this->games_stats($options);
        
        $controlPoint = [];
        $matches      = [];
        $doubleCP     = [];
        
        $mapsPlayer = array_count_values(array_column($games_stats, 'matchID'));
        
        foreach ($games_stats as $game) {
          $matchID = $game['matchID'];
          $info    = explode('=', $matchID);
          $teams   = explode('<>', $info[1]);
          
          $this->internal = $teams[0] == $teams[1];
          
          $this->match_init($matchID, $game, $teams, $matches);
          $this->game_init($game, $teams);
          $this->game_teams($game);
          
          if ( !$game['game_id'] ) $game['game_id'] = strtoupper(substr($game['type'], 0, 3)) . '-' . substr(wp_generate_uuid4(), -8);
          
          if ( !in_array($game['game_id'], array('forfeit', 'double-forfeit', 'map-forfeit')) ) {
            $this->game_round_time($game);
            $this->game_dropped_players($game);
            $this->game_players_sort($game);
          }
          else if ( in_array($game['game_id'], array('forfeit', 'map-forfeit')) ) $game['winner'] = $game['colors']['red'];
          else $game['winner'] = FALSE;
          
          // Only capture the first two wins.
          if (
            isset($_GET['all'])
            OR !$game['winner']
            OR ( $matches[$matchID]['teams'][$game['winner']]['score'] < 2 && !isset($matches[$matchID]['teams']['winner']) )
          ) {
            if ( $mapsPlayer[$matchID] > 3 && $game['type'] == 'ControlPoint' ) {
              if ( !isset($doubleCP[$matchID]) ) $doubleCP[$matchID] = [];
              $doubleCP[$matchID][] = $game;
              
              if ( count($doubleCP[$matchID]) > 1 ) $game = $this->double_cp($doubleCP[$matchID]);
              else continue;
            }
            
            if ( $game['winner'] ) $matches[$matchID]['teams'][$game['winner']]['score']++;
            
            if ( empty($game['type']) ) $matches[$matchID]['games'][] = $game;
            else $matches[$matchID]['games'][$game['game_id']] = $game;
            
            if ( $game['winner'] && $matches[$matchID]['teams'][$game['winner']]['score'] == 2 ) $matches[$matchID]['teams']['winner'] = $game['winner'];
          }
        }
        
        return $matches;
      }
      public function double_cp( $matches ) {
        $match = $this->double_cp_match($matches[0]);
        
        if ( $matches[0]['winner'] === $matches[1]['winner'] ) {
          $match['winner'] = $matches[1]['winner'];
          $match['notes']  = "Double CP decided by double win.";
          
          $winner = $matches[0]['winner'];
        }
        else {
          $winner = [];
          
          $data = [
            'kills'  => [],
            'score'  => [],
            'points' => [],
          ];
          
          $milleseconds = 0;
          
          foreach ($matches as $key => $m) {
            $teams = array_column($m['teams'], 'score'); sort($teams);
            $data['points'][$m['winner']] = $teams[1] - $teams[0];
            
            foreach ($m['teams'] as $team_name => $team) {
              if ( !isset($data['score'][$team_name]) ) $data['score'][$team_name] = 0;
              $data['score'][$team_name] += array_sum(array_column($team['players'], 'score'));
              
              if ( !isset($data['kills'][$team_name]) ) $data['kills'][$team_name] = 0;
              $data['kills'][$team_name] += array_sum(array_column($team['players'], 'kills'));
            }
          }
          
          $match['time'] = gmdate("H:i:s", $match['time']) .'.'.$milleseconds;
          
          foreach ($data as $type => $score) {
            $teams = array_keys($score);
            
            if ( $score[$teams[0]] == $score[$teams[1]] ) $winner[$type] = FALSE;
            else $winner[$type] = $score[$teams[0]] > $score[$teams[1]] ? $teams[0] : $teams[1];
          }
          
          $winner    = array_filter($winner);
          $winner_by = array_key_last($winner);
          
          $match['winner'] = $winner[$winner_by];
          
          if ( $winner_by ) {
            switch ($winner_by) {
              case 'points':
                $match['notes'] .= sprintf('Double CP decided by points differential<br>%s points', str_replace(array('=', '&'), array(' = ', ' VS '), http_build_query($data[$winner_by])));
              break;
              default:
                $match['notes'] = sprintf('Double CP decided by %s: <br>%s', $winner_by, str_replace(array('=', '&'), array(' = ', ' VS '), http_build_query($data[$winner_by])));
              break;
            }
          }
        }
        
        foreach ($matches as $key => $m) {
          foreach ($m['teams'] as $team_name => $team) {
            if ( !isset($match['teams'][$team_name]['name']) ) $match['teams'][$team_name]['name']   = $team['name'];
            if ( !isset($match['teams'][$team_name]['color']) ) $match['teams'][$team_name]['color'] = $team['color'];
            if ( !isset($match['teams'][$team_name]['score']) ) $match['teams'][$team_name]['score'] = 0;
            
            $match['teams'][$team_name]['score'] += $team['score'];
            
            if ( !isset($match['teams'][$team_name]['players']) ) $match['teams'][$team_name]['players'] = array_column($team['players'], NULL, 'name');
            else $this->double_cp_players($team['players'], $match['teams'][$team_name]['players']);
          }
        }
        
        return $match;
      }
      public function double_cp_match( $match ) {
        // fns::put($match);
        $new_match = $match;
        $new_match['time']   = 0;
        $new_match['winner'] = NULL;
        $new_match['teams']  = array_fill_keys(array_keys($match['teams']), []);
        
        unset($new_match['dropped_players']);
        unset($new_match['round_data']);
        
        return $new_match;
      }
      public function double_cp_players( $combine, &$players ) {
        foreach ($combine as $key => $p) {
          if ( isset($players[$p->name]) ) {
            foreach ($p as $key => $value) {
              if ( is_numeric($value) ) $players[$p->name]->$key = intval($players[$p->name]->$key) + $value;
            }
          }
          else $players[$p->name] = $p;
        }
      }
      public function games_stats( $options = array() ) {
        global $pxl, $wpdb;
        
        // $start = date('Ymd', strtotime($pxl->season['regular_start']));
        // $end   = date('Ymd', strtotime($pxl->season['playoffs_end']));
        // $where = $wpdb->prepare("AND gs.datetime >= '%s' AND gs.datetime <= '%s'", $start, $end);
        $where = '';
        $join  = isset($options['join']) ? $options['join'] : '';
        
        if ( isset($options['where']) ) $where .= sprintf(' AND %s', $options['where']);
        
        $sql = "
          SELECT *
          FROM dl_game_stats AS gs
          $join
          WHERE gs.matchID is not NULL AND gs.matchID != ''$where
          ORDER BY gs.datetime ASC, gs.matchID
        ";
        
        $game_stats = $wpdb->get_results($sql, ARRAY_A);
        
        return $game_stats;
      }
      private function game_init( &$game, $teams ) {
        $game['winner']          = strtolower($game['winner']);
        $game['red_players']     = !empty($game['red_players']) && strpos($game['red_players'], '"id"') ? json_decode($game['red_players']) : $game['red_players'];
        $game['blue_players']    = !empty($game['blue_players']) && strpos($game['blue_players'], '"id"') ? json_decode($game['blue_players']) : $game['blue_players'];
        $game['dropped_players'] = !empty($game['dropped_players']) && strpos($game['dropped_players'], '"id"') ? json_decode($game['dropped_players']) : array();
        $game['round_data']      = !empty($game['round_data']) ? json_decode(strtoupper($game['round_data'])) : array();
        
        if ( $this->internal ) {
          $tags_red  = "{$teams[0]}-1";
          $tags_blue = "{$teams[1]}-2";
        }
        else {
          $tags_red  = is_array($game['red_players']) ? current(array_intersect($teams, array_unique(array_column($game['red_players'], 'tag')))) : $game['red_players'];
          $tags_blue = is_array($game['blue_players']) ? current(array_intersect($teams, array_unique(array_column($game['blue_players'], 'tag')))) : $game['blue_players'];
        }
        
        $game['colors'] = array('red' => strtoupper($tags_red), 'blue' => strtoupper($tags_blue));
      }
      private function game_dropped_players( &$game ) {
        foreach ($game['dropped_players'] as $player) {
          if ( $player->score == 0 || empty($player->tag) ) continue;
          
          if ( isset($game['teams'][strtoupper($player->tag)]) ) $game['teams'][strtoupper($player->tag)]['players'][] = $player;
        }
      }
      private function game_players_sort( &$game ) {
        foreach ($game['teams'] as $team => $data) {
          $score = array_column($data['players'], 'score');
          array_multisort($score, SORT_DESC, $data['players']);
          $game['teams'][$team]['players'] = array_slice($data['players'], 0, 5);
        }
      }
      private function game_round_time( &$game ) {
        if ( !empty($game['round_data']) ) {
          if ( isset($game['round_data'][0]->TEAM) ) {
            if ( $this->internal ) {
              $game['round_data'][0]->TEAM = "{$game['round_data'][0]->TEAM}-1";
              $game['round_data'][1]->TEAM = "{$game['round_data'][1]->TEAM}-2";
            }
            
            $game['round_data'] = array_column($game['round_data'], 'ROUND_TIME', 'TEAM');
            arsort($game['round_data']);
            foreach ($game['round_data'] as $team => $time) $game['teams'][$team]['round_time'] = date('i:s', $time);
          }
        }
        
        if ( $game['winner'] === 'draw' ) $game['winner'] = array_key_last($game['round_data']);
        else $game['winner'] = $game['colors'][strtolower($game['winner'])];
      }
      private function game_teams( &$game ) {
        $game['teams'] = array(
          $game['colors']['red'] => array(
            'name'    => $game['colors']['red'],
            'color'   => 'red',
            'players' => $game['red_players'],
            'score'   => $game['type'] === 'Payload' ? ($game['red_score']/100) . '%' : $game['red_score'],
          ),
          $game['colors']['blue'] => array(
            'name'    => $game['colors']['blue'],
            'color'   => 'blue',
            'players' => $game['blue_players'],
            'score'   => $game['type'] === 'Payload' ? ($game['blue_score']/100) . '%' : $game['blue_score'],
          ),
        );
        
        unset($game['red_players']); unset($game['red_score']);
        unset($game['blue_players']); unset($game['blue_score']);
      }
      private function match_init( $matchID, $game, $teams, &$matches ) {
        if ( !isset($matches[$matchID]) ) {
          $datetime = DateTime::createFromFormat("Y-m-d H:i:s", "{$game['datetime']}");
          $datetime->setTimeZone(new DateTimeZone('America/New_York'));
          
          $match = array(
            'datetime' => $datetime,
            'teams'    => array(),
            'recorded' => $game['recorded'],
            'games'    => array(),
          );
          
          foreach ($teams as $key => $team) {
            $post = get_page_by_title($team, OBJECT, 'team');
            
            $index = $key+1;
            if ( $this->internal ) $team = "{$team}-{$index}";
            
            $match['teams'][strtoupper($team)] = array(
              'id'    => isset($post->ID) ? $post->ID : FALSE,
              'name'  => $team,
              'link'  => get_permalink($post),
              'logo'  => pxl::image($post, array( 'w' => 75, 'h' => 75, 'return' => 'tag' )),
              'score' => 0,
            );
          }
          
          $matches[$matchID] = $match;
        }
      }
      
    // Cycles
      public function cycles() {
        global $pxl, $wpdb;
        
        $sql = $wpdb->prepare("
          SELECT t.cycle, t.start, t.end
          FROM dl_tiers AS t
          WHERE season = %d
          GROUP BY cycle
        ", $pxl->season['number']);
        
        return $wpdb->get_results($sql, ARRAY_A);
      }
      
    // Display: Stats and Standings
      public function global() {
        global $wpdb, $pxl;
        
        if ( $list_season = get_query_var('list_season') ) {
          $slug   = $list_season;
          $parts  = explode('-', $list_season);
          $season = $parts[1];
        }
        else {
          $slug = 'current';
          $season = $pxl->season['number'];
        }
        
        $teams = sprintf("
          SELECT count(distinct p.id)
          FROM {$wpdb->prefix}posts as p
          JOIN {$wpdb->prefix}term_relationships AS tr ON tr.object_id = p.id
          JOIN {$wpdb->prefix}term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
          JOIN {$wpdb->prefix}terms AS t ON t.term_id = tt.term_id
          WHERE p.post_type = 'team' AND p.post_status = 'publish' AND t.slug = '%s'
        ", $slug);
        
        $players = sprintf("
          SELECT count(distinct p.id)
          FROM {$wpdb->prefix}posts as p
          JOIN {$wpdb->prefix}term_relationships AS tr ON tr.object_id = p.id
          JOIN {$wpdb->prefix}term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
          JOIN {$wpdb->prefix}terms AS t ON t.term_id = tt.term_id
          WHERE p.post_type = 'player' AND p.post_status = 'publish' AND t.slug = '%s'
        ", $slug);
        
        return array(
          'time'    => $wpdb->get_var("SELECT SEC_TO_TIME(SUM(TIME_TO_SEC( d.time ))) FROM dl_teams as d WHERE season = {$season}"),
          'players' => $wpdb->get_var($players),
          'teams' => $wpdb->get_var($teams),
        );
      }
      public function stats( $season = FALSE, $override = array(), $table = 'dl_players' ) {
        global $pxl, $wpdb;
        
        if ( !$season ) $season = $pxl->season['number'];
        
        $stats = array(
          'deaths'  => 'sum(d.deaths)',
          'kills'   => 'sum(d.kills)',
          'maps'    => 'COUNT(d.id)',
          'outcome' => 'SUM(d.outcome)',
          'score'   => 'SUM(d.score)',
          'time'    => '(SUM(TIME_TO_SEC(d.time)) / 60)',
        );
        
        $query = array(
          'select' => array(
            'd.name AS `Name`',
            'pl.seasons', 'pl.season_list',
            'SUBSTRING(MAX(CONCAT(d.id, d.team)), LENGTH(d.id) + 1) AS `Team`',
            'ROUND(AVG(opp.mmr) / 100, 2) as `SOPP | Strength of Opponent`',
            "ROUND({$stats['time']}, 2) AS `TTP | Total Time Played`",
            "{$stats['maps']} AS `TMP | Total Maps Played`",
            "{$stats['outcome']} AS `Map Wins`",
            "ROUND(100 * ({$stats['outcome']} / {$stats['maps']}), 1) AS `Map Win % | Win Percentage`",
            "ROUND({$stats['kills']} / {$stats['deaths']}, 2) AS `KD`",
            "{$stats['kills']} AS `Kills`",
            "{$stats['deaths']} AS `Deaths`",
            "SUM(d.headshots) AS `Headshots`",
            "SUM(d.shots) AS `Shots`",
            "SUM(d.shots_hit) AS `Hits`",
            "ROUND((d.shots_hit/d.shots) * 100, 1) AS `Accuracy`",
            "SUM(d.captures) AS `Captures`",
            "SUM(d.counters) AS `Counters`",
            "SUM(d.push_time) AS `Push (seconds)`",
            "ROUND({$stats['kills']} / {$stats['time']}, 2) AS `Kills/min`",
            "ROUND({$stats['kills']} / {$stats['maps']}, 2) AS `Kills/map`",
            "{$stats['score']} AS `Score`",
            "ROUND({$stats['score']} / {$stats['time']}, 2) AS `Score/min`",
            "ROUND({$stats['score']} / {$stats['maps']}, 2) AS `Score/map`",
            "{$stats['kills']} + {$stats['deaths']} AS `TI | Terminal Interactions`",
            "ROUND(({$stats['kills']} + {$stats['deaths']}) / {$stats['time']}, 2) AS `TI/min | Interactions/min`",
            "ROUND(({$stats['kills']} + {$stats['deaths']}) / {$stats['maps']}, 2) AS `TI/map | Interactions/map`",
            "modes",
          ),
          'join'  => array(),
          'where' => array()
        );
        
        $select_string = implode(",\n\t\t\t", $query['select']);
        $join_string   = implode(' ', $query['join']);
        $where_string  = implode(' ', $query['where']);
        
        $sql = sprintf('
            SELECT
            %1$s
            FROM %2$s as d
            JOIN ( SELECT team_id, SUM(mmr) as mmr FROM dl_teams WHERE season = %4$d GROUP BY team_id ) AS opp ON d.opponent_id = opp.team_id
            JOIN ( SELECT player_id, GROUP_CONCAT(DISTINCT season) as `season_list`, COUNT(DISTINCT season) as `seasons` FROM dl_players GROUP BY player_id ) AS pl ON d.player_id = pl.player_id
            JOIN (
              SELECT m.player_id, GROUP_CONCAT(DISTINCT m.type, \':\', m.maps, \':\', m.time, \':\', m.score, \':\', m.score_min) AS modes
              FROM (
                SELECT d.player_id, GROUP_CONCAT(DISTINCT season) as `seasons`, m.meta_value AS `type`, count(m.meta_value) as `maps`, (SUM(TIME_TO_SEC(d.time)) / 60) as `time`, SUM(d.score) as `score`, ROUND(SUM(d.score) / (SUM(TIME_TO_SEC(d.time)) / 60), 2) as `score_min`
                FROM dl_players as d
                JOIN wp_zoe0kio31p_postmeta AS m ON d.map_id = m.post_id AND m.meta_key = \'mode\'
                %3$s
                WHERE d.season = %4$d%5$s
                GROUP BY d.player_id, type
              ) AS m
              GROUP BY m.player_id
            ) as m ON d.player_id = m.player_id
            WHERE d.season = %4$d AND d.map_id IS NOT NULL AND d.name IS NOT NULL
            GROUP BY d.player_id
            ORDER BY d.name ASC
          ',
          $select_string,
          $table,
          $join_string,
          $season,
          $where_string
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
      }
      public function stats_top( $limit = FALSE, $SOPP = FALSE ) {
        global $pxl, $wpdb;
        
        $played = $pxl->cycle < 3 ? 3 : 6;
        $season = $pxl->season['number'];
        
        $sql_parts = array(
          'select' => "
            SELECT p.post_title as name, p.post_name as slug,
            SUBSTRING_INDEX(group_concat(DISTINCT d.team_id), ',', -1) AS team_id,
            count(d.map_id) as maps,
            %s as stat",
          'from'   => "FROM ( SELECT DISTINCT * FROM dl_players ORDER BY id DESC ) AS d",
          'join'   => "
            JOIN {$wpdb->prefix}posts AS p ON p.id = d.player_id
          ",
          'where'  => "WHERE d.season = {$season}",
          'group'  => 'GROUP BY d.player_id',
          'having' => "HAVING maps >= {$played}",
          'order'  => "ORDER BY stat DESC, SOPP DESC",
        );
        
        $sql_parts['select'] .= ", s.SOPP";
        $sql_parts['join'] .= "
          LEFT JOIN (
            SELECT d.player_id, AVG(opp.mmr)/100 as SOPP, COUNT(d.id) as maps
            FROM dl_players AS d
            JOIN wp_zoe0kio31p_posts AS p ON p.id = d.player_id
            LEFT JOIN (
              SELECT team_id, SUM(mmr) as mmr
              FROM dl_teams AS t
              WHERE season = {$season}
              GROUP BY team_id
            ) AS opp ON opp.team_id = d.opponent_id
            WHERE d.season = {$season}
            GROUP BY d.player_id
            HAVING maps >= {$played}
            ORDER BY SOPP DESC
          ) AS s ON s.player_id = d.player_id
        ";
        
        if ( $SOPP ) {
          $sopp_avg = $wpdb->get_var("
            SELECT sum(sp.SOPP)/count(sp.SOPP)
            FROM (
              SELECT p.post_title as player, ROUND(AVG(opp.mmr)/100, 2) as SOPP, COUNT(d.id) as maps
              FROM dl_players AS d
              JOIN wp_zoe0kio31p_posts AS p ON p.id = d.player_id
              LEFT JOIN (
                SELECT team_id, SUM(mmr) as mmr
                FROM dl_teams AS t
                WHERE season = {$season}
                GROUP BY team_id
              ) AS opp ON opp.team_id = d.opponent_id
              WHERE d.season = {$season}
              GROUP BY d.player_id
              HAVING maps >= {$played}
              ORDER BY SOPP DESC
            ) AS sp
          ");
          
          $sql_parts['having'] .= " AND SOPP >= $sopp_avg";
        }
        
        if ( $limit ) $sql_parts['limit'] = "LIMIT {$limit}";
        
        $stats = $this->stats_general($sql_parts);
        $this->stats_sopp($stats, $played);
        // $this->stats_modes($sql_parts, $stats);
        
        return $stats;
      }
      private function stats_general( $sql_parts ) {
        global $wpdb;
        
        $sql = implode(' ' , $sql_parts);
        
        $per_minute = '(SUM((TIME_TO_SEC(d.time))/60))';
        
        $stats = array(
          'K/D'              => 'ROUND(sum(d.kills)/sum(d.deaths), 2)',
          'Kills'            => 'sum(d.kills)',
          'Kills/min'        => "ROUND(sum(d.kills) / $per_minute, 2)",
          'Win Percentage'   => 'ROUND(100 * sum(d.outcome) / count(d.map_id), 2)',
          'Score'            => 'sum(d.score)',
          'Score/min'        => "ROUND(sum(d.score) / $per_minute, 2)",
          'Interactions'     => 'sum(d.kills) + sum(d.deaths)',
          'Interactions/min' => "ROUND((sum(d.kills) + sum(d.deaths)) / $per_minute, 2)",
        );
        
        foreach ($stats as $key => $value) {
          $s = sprintf($sql, $value);
          $players = $wpdb->get_results(sprintf($s, $value));
          $stats[$key] = $players;
        }
        
        return $stats;
      }
      private function stats_modes( $sql_parts, &$stats ) {
        global $wpdb;
        
        $map_types = array(
          'Domination|wins'    => 'domination',
          'Payload|wins'       => 'payload',
          'Control Point|wins' => 'control-point'
        );
        
        $sql_parts['join'] .= "
          JOIN {$wpdb->prefix}posts AS m ON m.id = d.map_id
          JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = m.id AND pm.meta_key = 'mode'
        ";
        
        $sql_parts['having'] = "HAVING maps > 3";
        
        foreach ($map_types as $name => $mode) {
          $sql_parts['where'] = sprintf("WHERE pm.meta_value = '%s'", $mode);
          
          $sql = implode(' ' , $sql_parts);
          $stats[$name] = $wpdb->get_results(sprintf($sql, 'sum(d.outcome)'));
        }
      }
      private function stats_sopp( &$stats, $played = 6 ) {
        global $pxl, $wpdb;
        
        $season = $pxl->season['number'];
        
        $stats['Strength of Opponent'] = $wpdb->get_results("
          SELECT p.post_title as name, p.post_name as slug, SUBSTRING_INDEX(group_concat(DISTINCT d.team_id), ',', -1) AS team_id, ROUND(AVG(DISTINCT opp.mmr)/100, 2) as stat, COUNT(d.id) as maps
          FROM dl_players AS d
          JOIN {$wpdb->prefix}posts AS p ON p.id = d.player_id
          LEFT JOIN (
            SELECT team_id, SUM(mmr) as mmr
            FROM dl_teams AS t
            WHERE t.season = {$season}
            GROUP BY team_id
          ) AS opp ON opp.team_id = d.opponent_id
          WHERE d.season = {$season}
          GROUP BY d.player_id
          HAVING maps >= {$played}
          ORDER BY stat DESC
          LIMIT 10
        ");
      }
      
    // Helpers
      public function list( $opts = array() ) {
        global $wpdb;
        
        $prefix = $wpdb->prefix;
        
        $args = array_merge(array(
          'fields'  => '*',
          'join'    => FALSE,
          'where'   => FALSE,
          'groupby' => '',
          'orderby' => '',
          'table'   => 'dl_teams',
        ), $opts);
        
        $sql = array(
          'select' => "SELECT {$args['fields']}",
          'from'   => "FROM {$args['table']} as data",
        );
        
        if ( $args['join'] ) $sql['join'] = $args['join'];
        else {
          if ( $args['table'] === 'dl_players' ) {
            $sql['join'] = "
              JOIN {$prefix}posts AS team ON team.id = data.team_id
              JOIN {$prefix}posts AS map ON map.id = data.map_id
              JOIN {$prefix}posts AS player ON player.id = data.player_id
            ";
          }
          else if ( $args['table'] === 'dl_teams' ) {
            $sql['join'] = "
              JOIN {$prefix}posts AS team ON team.id = data.team_id
              JOIN {$prefix}posts AS opp ON opp.id = data.opponent_id
              JOIN {$prefix}postmeta AS tmeta ON tmeta.post_id = team.id AND tmeta.meta_key = 'rank_mmr'
              LEFT JOIN {$prefix}posts AS map ON map.id = data.map_id
            ";
          }
        }
        
        if ( $args['join'] ) $sql['join'] .= $args['join'];
        
        if ( $args['where'] ) $sql['where'] = "WHERE {$args['where']}";
        
        if ( $args['groupby'] ) $sql['groupby'] = "GROUP BY {$args['groupby']}";
        
        if ( $args['orderby'] ) $sql['orderby'] = "ORDER BY {$args['orderby']}";
        
        $sql = implode(' ' , $sql);
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        return $results;
      }
      public function team( $team_id ) {
        global $pxl, $wpdb;
        
        $season = $pxl->season['number'];
        // todo fix 1000
        $sql = "
          SELECT r1.name, sum(r1.MMR) + 1000 as mmr, count(r1.Matches) as matches, sum(r1.wins) as won, count(r1.Matches) - sum(r1.wins) as lost, ROUND(SUM(r1.kills)/SUM(r1.deaths), 2) as kd, SEC_TO_TIME(SUM(time_to_sec(r1.time))) as time_played
          FROM (
            SELECT t1.name, sum(t1.mmr) as MMR, count(DISTINCT t1.matchID) as matches, t2.wins, sum(t1.kills) as kills, sum(t1.deaths) as deaths, SEC_TO_TIME(SUM(time_to_sec(t1.time))) as time
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
              WHERE season = {$season}
              GROUP BY team_id, matchID
              ORDER BY name ASC
            ) as t2 on t1.matchID = t2.matchID AND t2.team_id = t1.team_id
            WHERE t1.season = {$season} and t1.team_id = '{$team_id}'
            GROUP BY t1.team_id, t1.matchID
            ORDER BY name ASC
          ) as r1
          GROUP BY r1.name
          ORDER BY MMR DESC
        ";
        
        $items = $wpdb->get_row($sql);
        
        return $items;
      }
  }
}

/*
1139
20230211=HAXX<>guh
PAY-78603a74
[{"id":"3dabec02-c07c-c15b-4bac-de0804fecf0b","name":"Pistol Shrimp","tag":null,"kills":22,"deaths":51,"shots":719,"shots_hit":102,"damage":2891,"headshots":11,"score":3851,"push_time":97}]
*/