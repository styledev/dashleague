<?php if ( !class_exists('dlRecords') ) {
  class dlRecords {
    public $data, $maps, $teams;
    
    function __construct( $data ) {
      global $pxl;
      
      $this->data   = $data;
      $this->maps   = count($data);
      $this->season = $pxl->season['number'];
      $this->teams  = array_column($data[0]['teams'], 'team_id', NULL);
    }
    
    public function record_stats() {
      foreach ($this->data as $key => $match) {
        $info = $match['info'];
        $info['season'] = $this->season;
        
        foreach ($match['teams'] as $index => $team) {
          $players = isset($team['players']) ? $team['players'] : FALSE;
          
          $this->set_team($team);
          $this->set_opponent($team, $match, $index);
          
          if ( $players ) {
            unset($team['players']);
            
            foreach ($players as $player) {
              $team['kills']  += $player['kills'];
              $team['deaths'] += $player['deaths'];
              $team['score']  += $player['score'];
              
              // Save Player Record
              $this->record_stat('players', array_merge($info, $team, $player));
            }
          }
          
          // Record Gains
            if ( ($key + 1) == $this->maps ) {
              
              if ( isset($this->winner['mmr']) ) {
                $team['mmr'] = $team['team_id'] == $this->winner['team_id'] ? $this->winner['mmr'] : $this->loser['mmr'];
              }
              
              if ( isset($this->winner['rank_gain']) ) {
                $team['rank_gain'] = $team['team_id'] == $this->winner['team_id'] ? $this->winner['rank_gain'] : $this->loser['rank_gain'];
              }
            }
            
          // Save Team Record
          $this->record_stat('teams', array_merge($info, $team));
        }
        
      }
    }
    
    private function record_stat( $table, $data ) {
      global $wpdb;
      
      $wpdb->insert("dl_{$table}", $data);
    }
    
    private function get_team_name( $team_id ) {
      $post = get_post($team_id);
      
      return $post->post_title;
    }
    
    private function set_opponent( &$details, $match, $index ) {
      $opponent = $index == 1 ? $match['teams'][0] : $match['teams'][1];
      
      $details['opponent_id'] = $opponent['team_id'];
      $details['opponent']    = $this->get_team_name($opponent['team_id']);
    }
    private function set_team( &$team ) {
      $team = array_merge($team, ['name' => $this->get_team_name($team['team_id']), 'kills' => 0, 'deaths' => 0, 'score' => 0]);
    }
  }
}