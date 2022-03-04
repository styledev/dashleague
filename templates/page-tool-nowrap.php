<?php /* Template Name: Tool */
  class hdStats {
    function __construct() {
      global $wpdb;
      
      $this->db = $wpdb;
      $this->hd = new wpdb('root', '', 'hyperdashstats', '127.0.0.1');
      
      $this->init();
    }
    
    public function init() {
      $this->get_games_stats();
      $this->get_match_ids();
      $this->teams = array();
    }
    public function games( $action = 'all' ) {
      foreach ($this->games as $gs) {
        if ( strpos($gs['game_id'], 'forfeit') !== FALSE ) continue;
        
        $red        = json_decode($gs['red_players']); unset($gs['red_players']);
        $blue       = json_decode($gs['blue_players']); unset($gs['blue_players']);
        $dropped    = json_decode($gs['dropped_players']); unset($gs['dropped_players']);
        $round_data = json_decode($gs['round_data']); unset($gs['round_data']);
        
        $team_red  = array_column($red, 'tag')[0];
        $team_blue = array_column($blue, 'tag')[0];
        
        if ( $action == 'matches' ) {
          $data = array(
            'game_id'    => $gs['game_id'],
            'match_id'   => $this->match_ids[$gs['matchID']],
            'datetime'   => $gs['datetime'],
            'mode'       => $gs['type'],
            'map'        => $this->map_name($gs['map']),
            'time'       => $gs['time'],
            'winner'     => strtolower($gs['winner']),
            'red_team'   => $team_red,
            'red_score'  => $gs['red_score'],
            'red_push'   => 0,
            'blue_team'  => $team_blue,
            'blue_score' => $gs['blue_score'],
            'blue_push'  => 0,
            'round_data' => NULL
          );
          
          switch ($data['mode']) {
            case 'Elimination':
              continue 2;
            break;
            case 'Domination':
              if ( is_array($round_data) ) {
                $data['round_data'] = implode('|', array_column($round_data, 'round_time'));
              }
            break;
            case 'Payload':
              if ( !empty($round_data) ) {
                foreach ($round_data as $key => $rd) {
                  $team = $key === 0 ? 'red_push' : 'blue_push';
                  if ( isset($rd->team) ) $team = strtolower($rd->team) == strtolower($team_red) ? 'red_push' : 'blue_push';
                
                  $data[$team] = $rd->round_time;
                }
              }
            break;
            default:break;
          }
          
          $this->hd_save_match($data['match_id'], array($team_red, $team_blue));
          $this->hd_save_game_stats($data);
          $this->hd_save_game_players_stats($data, $red, $blue, $dropped);
        }
        elseif ( $action == 'teams' ) {
          $this->teams[] = $team_red;
          $this->teams[] = $team_blue;
        }
      }
      
      if ( $action == 'teams' ) $this->hd_save_teams();
    }
    
    private function get_games_stats() {
      $this->games = $this->db->get_results("
        SELECT *
        FROM dl_game_stats
        WHERE recorded IS NULL
        ORDER BY datetime ASC
      ", ARRAY_A);
    }
    private function get_match_ids() {
      $match_ids = $this->db->get_results("
        SELECT matchID, group_concat(game_id ORDER BY datetime ASC SEPARATOR '_') as newID
        FROM dl_game_stats
        WHERE recorded IS NULL
        GROUP BY matchID
      ", ARRAY_A);
      
      $this->match_ids = array_column($match_ids, 'newID', 'matchID');
    }
    private function hd_save_game_stats( $data ) {
      global $hd;
      
      $format = $this->data_format($data);
      $result = $this->hd->replace('hd_game_stats', $data, $format);
    }
    private function hd_save_game_players_stats( $data, $red, $blue, $dropped ) {
      $winner = $data["{$data['winner']}_team"];
      
      $fields = array('game_id', 'match_id', 'datetime', 'mode', 'map', 'time');
      
      $data = array_filter($data, function($value, $key) use($fields) {
        return in_array($key, $fields);
      }, ARRAY_FILTER_USE_BOTH);
      
      if ( !empty($dropped) ) {
        $red_team  = $red[0]->tag;
        $blue_team = $blue[0]->tag;
        
        foreach ($dropped as $key => $player) {
          if ( $player->score > 0 ) {
            if ( $player->tag == $red_team ) $red[] = $player;
            else if ( $player->tag == $blue_team ) $blue[] = $player;
          }
        }
      }
      
      $players = array_merge($red, $blue);
      
      foreach ($players as $key => $player) {
        $player_data = array_merge($data, (array)$player);
        $player_data['guid'] = $player_data['id']; unset($player_data['id']);
        $player_data['won'] = $player_data['tag'] == $winner;
        
        $format = $this->data_format($player_data);
        $this->hd->replace('hd_game_player_stats', $player_data, $format);
      }
    }
    private function hd_save_match( $match_id, $teams ) {
      foreach ($teams as $team) {
        $data = array(
          'collection_id' => 1,
          'team'          => $team,
          'match_id'      => $match_id
        );
        
        $format = $this->data_format($data);
        $this->hd->insert('hd_matches', $data, $format);
      }
    }
    private function hd_save_teams() {
      $teams = array_unique($this->teams);
      sort($teams);
      
      foreach ($teams as $team) {
        $team = array('name' => $team, 'collection_id' => 1);
        $format = $this->data_format($team);
        $this->hd->insert('hd_teams', $team, $format);
      }
    }
    private function map_name( $name ) {
      $convert = array(
        'Payload_Blue_Art'     => 'pay_canyon',
        'Payload_Orange_Art'   => 'pay_launchpad',
        'Domination_Yellow'    => 'dom_waterway',
        'ControlPoint_Stadium' => 'cp_stadium',
        'Domination_Grey'      => 'dom_quarry',
      );
      
      if ( isset($convert[$name]) ) $name = $convert[$name];
      
      return $name;
    }
    
    /* Database Helpers */
    private function data_format( $data ) {
      $format = array();
      
      foreach ($data as $d) array_push($format, $this->var_type($d));
      
      return $format;
    }
    private function var_type( $var ) {
      if ( is_string($var) && is_numeric($var) && $var[0] != 0) $var = intval($var);
      
      switch (getType($var)) {
        case 'double': return '%f'; break;
        case 'boolean': case 'integer': return '%d'; break;
        default: return '%s'; break;
      }
    }
  }
?>

<div class="content">
<?php
  $tool = new hdStats();
  $tool->games('matches');
?>
</div>