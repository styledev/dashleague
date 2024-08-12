<?php if ( !class_exists('dlRP') ) {
  include('class.records.php');
  
  class dlRP extends dlRecords {
    public $scoring;
    
    function __construct( $data ) {
      parent::__construct($data);
      
      $this->scoring = array_fill_keys(
        array_column($this->data[0]['teams'], 'team_id', 'team_id'),
        ['score' => 0, 'bonus' => 0]
      );
      
      $this->rank_data();
    }
    
    private function rank_data() {
      $forfeit_match = FALSE;
      
      foreach ($this->data as $key => $match) {
        $forfeit = $this->rank_match($match);
        
        if ( $forfeit ) {
          $this->maps = 0;
          break;
        }
      }
      
      $this->rank_final();
      
      fns::error($this->winner);
      fns::error($this->loser);
      
      parent::record_stats();
    }
    private function rank_match( $match ) {
      $map   = $match['info']['map'];
      $teams = $match['teams'];
      
      // Outcome Score
        if ( $teams[0]['outcome'] ) $this->scoring[$teams[0]['team_id']]['score']++;
        if ( $teams[1]['outcome'] ) $this->scoring[$teams[1]['team_id']]['score']++;
        
      // Match Forfeit
        if ( $match['info']['game_id'] == 'forfeit' ) {
          if ( $teams[0]['outcome'] ) $this->scoring[$teams[0]['team_id']]['score'] = 3;
          if ( $teams[1]['outcome'] ) $this->scoring[$teams[1]['team_id']]['score'] = 3;
          
          return TRUE;
        }
        
      // Map Bonus Points
        $fn = "rank_bonus_{$match['info']['type']}";
        
        if ( method_exists($this, $fn) ) {
          $this->$fn($map, $teams, 0, 1);
          $this->$fn($map, $teams, 1, 0);
        }
        
      return FALSE;
    }
    private function rank_bonus_domination( $map, $teams, $team, $opp ) {
      if ( (int)$teams[$team]['score_points'] === 3 && (int)$teams[$opp]['score_points'] == 0 ) {
        $players_team = count($teams[$team]['players']);
        $players_opp  = count($teams[$opp]['players']);
        
        if ( $players_team <= $players_opp ) $this->scoring[$teams[$team]['team_id']]['bonus']++;
      }
    }
    private function rank_bonus_payload( $map, $teams, $team, $opp ) {
      $qualifier = $map == 'pay_canyon' ? 100 : 90;
      
      if ( (float)$teams[$team]['score_percentage'] >= $qualifier && (float)$teams[$opp]['score_percentage'] <= 60.00 ) {
        $players_team = count($teams[$team]['players']);
        $players_opp  = count($teams[$opp]['players']);
        
        if ( $players_team <= $players_opp ) $this->scoring[$teams[$team]['team_id']]['bonus']++;
      }
    }
    private function rank_final() {
      $scoring    = array_values($this->scoring);
      $comparison = $scoring[0]['score'] > $scoring[1]['score'];
      
      // Set Winnning Team
        $this->winner = [
          'team_id'   => $comparison ? $this->teams[0] : $this->teams[1],
          'rank_gain' => $comparison ? array_sum($scoring[0]) : array_sum($scoring[1]),
          'outcome'   => 1,
        ];
      
      // Set Losing Team
        $this->loser = [
          'team_id'   => $comparison ? $this->teams[1] : $this->teams[0],
          'rank_gain' => $comparison ? array_sum($scoring[1]) : array_sum($scoring[0]),
          'outcome'   => 0,
        ];
        
      // Bonus for winning in 2 maps
        if ( $this->maps == 2 ) $this->winner['rank_gain']++;
    }
  }
}

