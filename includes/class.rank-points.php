<?php if ( !class_exists('dlRP') ) {
  include('class.records.php');
  
  class dlRP extends dlRecords {
    // private 
    function __construct() {}
    
    public function rank_data( $data ) {
      $maps    = count($data);
      $teams   = array_column($data[0]['teams'], 'team_id', NULL);
      $scoring = array_fill_keys(
        array_column($data[0]['teams'], 'team_id', 'team_id'),
        ['score' => 0, 'bonus' => 0]
      );
      
      foreach ($data as $key => $match) $this->rank_match($match, $scoring);
      
      [$winner, $loser] = $this->rank_final(array_values($scoring), $maps);
      
      fns::error("Maps played: $maps");
      fns::error("Winner is {$teams[$winner['id']]} with RP of {$winner['score']}");
      fns::error("Loser is {$teams[$loser['id']]} with RP of {$loser['score']}");
      
      // next up we need to save team and player data
      
      return FALSE;
    }
    private function rank_match( $match, &$scoring ) {
      $map   = $match['info']['map'];
      $teams = $match['teams'];
      
      // Outcome Score
        if ( $teams[0]['outcome'] ) $scoring[$teams[0]['team_id']]['score']++;
        if ( $teams[1]['outcome'] ) $scoring[$teams[1]['team_id']]['score']++;
        
      // Map Bonus Points
        $fn = "rank_bonus_{$match['info']['type']}";
        
        if ( method_exists($this, $fn) ) {
          $this->$fn($scoring, $map, $teams, 0, 1);
          $this->$fn($scoring, $map, $teams, 1, 0);
        }
    }
    private function rank_bonus_domination( &$scoring, $map, $teams, $team, $opp ) {
      if ( (int)$teams[$team]['score_points'] === 3 && (int)$teams[$opp]['score_points'] == 0 ) {
        $players_team = count($teams[$team]['players']);
        $players_opp  = count($teams[$opp]['players']);
        
        if ( $players_team <= $players_opp ) $scoring[$teams[$team]['team_id']]['bonus']++;
      }
    }
    private function rank_bonus_payload( &$scoring, $map, $teams, $team, $opp ) {
      $qualifier = $map == 'pay_canyon' ? 100 : 90;
      
      if ( (float)$teams[$team]['score_percentage'] >= $qualifier && (float)$teams[$opp]['score_percentage'] <= 60.00 ) {
        $players_team = count($teams[$team]['players']);
        $players_opp  = count($teams[$opp]['players']);
        
        if ( $players_team <= $players_opp ) $scoring[$teams[$team]['team_id']]['bonus']++;
      }
    }
    private function rank_final( $scoring, $maps ) {
      $outcome = [];
      
      if ( $scoring[0]['score'] > $scoring[1]['score'] ) {
        $outcome['winner'] = ['id' => 0, 'score' => array_sum($scoring[0])];
        $outcome['loser']  = ['id' => 1, 'score' => array_sum($scoring[1])];
      }
      else {
        $outcome['winner'] = ['id' => 1, 'score' => array_sum($scoring[1])];
        $outcome['loser']  = ['id' => 0, 'score' => array_sum($scoring[0])];
      }
      
      // Bonus for winning in 2 maps
        if ( $maps == 2 ) $outcome['winner']['score']++;
        
      return array_values($outcome);
    }
  }
}

