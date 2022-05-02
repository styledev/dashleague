<?php if ( !class_exists('dlMMR') ) {
  class dlMMR {
    function __construct() {
      global $pxl;
      
      $this->vars = array(
        'gain_min_loss' => 5,
        'gain'          => 105,
        'gain_win'      => 5,
        'k'             => 3.5,
        'rank_factor'   => 2000,
      );
      
      $this->cycles  = $pxl->stats->cycles();
      $this->rolling = array_slice($this->cycles, -3, 1)[0];
      $this->cycle   = array_pop($this->cycles);
    }
    
    // Styledev: I decided to be stupid and play demeo while coding the MMR
    
    // API
      public function match( $match ) {
        global $pxl, $wpdb;
        
        $i     = 1;
        $count = count($match);
        $teams = !empty($match[0]['teams']) ? $match[0]['teams'] : $match[1]['teams'];
        $mmr   = array_fill_keys(array_column($teams, 'team_id'), array());
        
        $this->match_teams($teams);
        
        /* Process Match Maps */
          foreach ($match as $mode => $data) {
            $this->match_info($data, $teams);
            
            /* Determine Rank Gain/Loss */
              if ( !$this->match_forfeit($data) ) {
                /* Map Mode Score */
                  $mode_formula = sprintf('formula_%s', $data['info']['type']);
                  $this->$mode_formula($data);
                  
                /* Gain/Loss */
                  $data['teams'][0]['rank_gain'] = $this->formula_mmr($data['teams'][0]);
                  $data['teams'][1]['rank_gain'] = $this->formula_mmr($data['teams'][1]);
              }
              
            /* Store MMR */
              $mmr[$data['teams'][0]['team_id']][] = $data['teams'][0]['rank_gain'];
              $mmr[$data['teams'][1]['team_id']][] = $data['teams'][1]['rank_gain'];
              
            /* Save Data */
              foreach ($data['teams'] as $key => $team) {
                unset($team['pushrate']);
                unset($team['rank']);
                unset($team['rank_gain']);
                unset($team['score']);
                unset($team['time']);
                
                $players = isset($team['players']) ? $team['players'] : FALSE; if ( $players ) unset($team['players']);
                $team    = array_merge($team, array('deaths' => 0, 'kills' => 0, 'score' => 0));
                
                // Opponent ID
                  $team['opponent_id'] = $key == 0 ? $data['teams'][1]['team_id'] : $data['teams'][0]['team_id'];
                  $team['opponent']    = $key == 0 ? $data['teams'][1]['name'] : $data['teams'][0]['name'];
                  
                // Format Score Time
                  if ( isset($team['score_time']) && strlen($team['score_time']) < 8 ) $team['score_time'] = "00:{$team['score_time']}";
                  
                // Players
                if ( $players ) {
                  foreach ($players as $player) {
                    $id_rank = explode('|', $player['player_id']);
                    $player['player_id']   = $id_rank[0];
                    $player['opponent_id'] = $team['opponent_id'];
                    $player['opponent']    = $team['opponent'];
                    
                    $team['deaths'] += $player['deaths'];
                    $team['kills']  += $player['kills'];
                    $team['score']  += $player['score'];
                    
                    $this->store_player_stat($data['info'], $team, $player);
                  }
                }
                
                // Only calculate team rank g ain on last map to get average
                  if ( $i === $count ) {
                    $value = $mmr[$team['team_id']];
                    $value = array_sum($value) / count($value);
                    
                    if ( $value > 0 && $value < 5 ) $value = 5;
                    else if ( $value > -5 && $value < 0 ) $value = -5;
                    
                    $team['rank_gain'] = $value;
                  }
                  
                // Store Team Data
                  $this->store_team_stat($data['info'], $team);
              }
              
            /* Update Match */
              $match[$mode] = $data;
              
            $i++;
          }
          
        /* Determine Winner */
          $winner = array_count_values(array_column($match, 'winner')); arsort($winner);
          $winner = array_slice(array_keys($winner), 0, 5, true);
          $match['winner'] = $winner[0];
          
        /* Final MMR Gain/Loss */
          foreach ($mmr as $team_id => $gain_loss) {
            $value = array_sum($gain_loss) / count($gain_loss);
            
            if ( $team_id == $match['winner'] && $value < 5 ) $value = 5;
            else if ( $team_id != $match['winner'] && $value > -5 ) $value = -5;
            
            $mmr[$team_id] = $value;
          }
          
        /* Save Report */
          $this->match_report($match, $mmr);
        
        return TRUE;
      }
      private function match_forfeit( &$data ) {
        global $wpdb, $pxl;
        
        if ( strpos($data['info']['game_id'], 'forfeit') !== FALSE ) {
          $tier       = $wpdb->get_var($wpdb->prepare("SELECT tier FROM dl_tiers WHERE season = %d AND cycle = %d AND team_id = %d", $pxl->season['number'], $this->cycle['cycle'], $data['teams'][1]['team_id']));
          $tier_teams = implode(', ', $wpdb->get_col($wpdb->prepare("SELECT team_id FROM dl_tiers WHERE season = %d AND cycle = %d AND tier = '%s'", $pxl->season['number'], $this->cycle['cycle'], $tier)));
          $mmr_avg    = array(
            'tier'    => $wpdb->get_var($wpdb->prepare("SELECT ROUND(AVG(rank_gain)) as tier_avg FROM dl_teams WHERE season = %d AND datetime >= '%s' AND datetime <= '%s' AND team_id IN ({$tier_teams}) AND rank_gain > 0", $pxl->season['number'], $this->cycle['start'], $this->cycle['end'])),
            'rolling' => $wpdb->get_var($wpdb->prepare("SELECT ROUND(AVG(rank_gain)) as tier_avg FROM dl_teams WHERE season = %d AND datetime >= '%s' AND datetime <= '%s' AND team_id IN ({$tier_teams}) AND rank_gain > 0", $pxl->season['number'], $this->rolling['start'], $this->cycle['end'])),
            'team'    => $wpdb->get_var($wpdb->prepare("SELECT ROUND(AVG(rank_gain)) as tier_avg FROM dl_teams WHERE season = %d AND team_id >= %d AND rank_gain > 0", $pxl->season['number'], $data['teams'][0]['team_id'])),
          );
          
          $mmr = max(array_values($mmr_avg));
          
          switch ($data['info']['game_id']) {
            case 'forfeit':
              $data['teams'][0]['rank_gain'] = -$mmr;
              $data['teams'][0]['notes']     = 'Loss from forfeit';
              $data['teams'][1]['rank_gain'] = $mmr;
              $data['teams'][1]['notes']     = 'Win from forfeit';
            break;
            case 'double-forfeit':
              $opp_mmr = $wpdb->get_var($wpdb->prepare("SELECT ROUND(AVG(rank_gain)) as tier_avg FROM dl_teams WHERE season = %d AND team_id >= %d AND rank_gain > 0", $pxl->season['number'], $data['teams'][1]['team_id']));
              $opp_mmr = max(array($mmr, $opp_mmr));
              
              $data['teams'][0]['rank_gain'] = -$mmr;
              $data['teams'][0]['notes']     = 'Loss from double forfeit';
              $data['teams'][1]['rank_gain'] = -$opp_mmr;
              $data['teams'][1]['notes']     = 'Loss from double forfeit';
            break;
            case 'map-forfeit':
              $data['teams'][0]['rank_gain'] = -$mmr;
              $data['teams'][0]['notes']     = 'Loss from map forfeit';
              $data['teams'][1]['rank_gain'] = $mmr;
              $data['teams'][1]['notes']     = 'Win from map forfeit';
            break;
            default:break;
          }
          
          return TRUE;
        }
        
        return FALSE;
      }
      private function match_info( &$data, $teams ) {
        global $pxl;
        
        /* Team Info */
          $data['winner'] = $data['teams'][0]['outcome'] == 1 ? $data['teams'][0]['team_id'] : $data['teams'][1]['team_id'];
          
          $teams = array_column($teams, null, 'team_id');
          
          $data['teams'][0]['name'] = $teams[$data['teams'][0]['team_id']]['name'];
          $data['teams'][0]['rank'] = $teams[$data['teams'][0]['team_id']]['rank'];
          
          $data['teams'][1]['name'] = $teams[$data['teams'][1]['team_id']]['name'];
          $data['teams'][1]['rank'] = $teams[$data['teams'][1]['team_id']]['rank'];
          
        /* Season Data */
          $data['info']['season'] = $pxl->season['number'];
          if ( strlen($data['info']['time']) < 8 ) $data['info']['time'] = "00:{$data['info']['time']}";
          
        /* MatchID */
          if ( !isset($data['info']['matchID']) ) {
            $date = date_create_from_format("F d Y", implode(' ', $data['info']['date'])); unset($data['info']['date']);
            
            $data['info']['datetime'] = $date->format("Y-m-d 00:00:00");
            $data['info']['matchID']  = $date->format("Ymd");
            
            $teams = array_column($data['teams'], 'name'); sort($teams);
            
            $data['info']['matchID'] .= "={$teams[0]}<>{$teams[1]}";
          }
      }
      private function match_teams( &$teams ) {
        foreach ($teams as $key => $team) {
          $team['name'] = get_the_title($team['team_id']);
          
          $opening = $this->team_rank($team['team_id']);
          
          $team['rank'] = array(
            'opening'  => $opening,
            'quotient' => pow(10, $opening / $this->vars['rank_factor']),
            'escore'   => NULL
          );
          
          $teams[$key] = $team;
        }
        
        $team_a = $teams[0];
        $team_b = $teams[1];
        
        $teams[0]['rank']['escore'] = $team_a['rank']['quotient'] / ( $team_a['rank']['quotient'] + $team_b['rank']['quotient'] ) * ( $this->vars['gain'] + $this->vars['gain_win'] );
        $teams[1]['rank']['escore'] = $team_b['rank']['quotient'] / ( $team_b['rank']['quotient'] + $team_a['rank']['quotient'] ) * ( $this->vars['gain'] + $this->vars['gain_win'] );
      }
      private function match_report( $match, $mmr ) {
        $dir = wp_upload_dir();
        
        ob_start();
          echo "\nMATCH\n";
          fns::put($match);
          echo "\nMMR\n";
          fns::put($mmr);
        $content = str_replace(array('<pre>', '</pre>'), '', ob_get_clean());
        
        file_put_contents(
          "{$dir['basedir']}/mmr/{$match[0]['info']['matchID']}.txt",
          $content,
          FILE_APPEND
        );
      }
      
    // Formulas
      private function formula_mmr( $team ) {
        if ( $team['outcome'] ) {
          return max(
            ($team['score'] - $team['rank']['escore']) * $this->vars['k'],
            $this->vars['gain_min_loss']
          );
        }
        else {
          return min(
            ($team['score'] - $team['rank']['escore']) * $this->vars['k'],
            -$this->vars['gain_min_loss']
          );
        }
      }
      private function formula_controlpoint( &$data ) {
        /* Team Scores*/
          $data['teams'][0]['score'] = $this->formula_controlpoint_score($data['teams'][0], $data['teams'][1]);
          $data['teams'][1]['score'] = $this->formula_controlpoint_score($data['teams'][1], $data['teams'][0]);
      }
      private function formula_controlpoint_score( $team, $opponent ) {
        $win_gain = $team['outcome'] ? $this->vars['gain_win'] : 0;
        
        return ( $team['score_points'] / ( $team['score_points'] + $opponent['score_points'] ) ) * $this->vars['gain'] + $win_gain;
      }
      private function formula_domination( &$data ) {
        $args = array(
          'time' => array(
            'played'     => strtotime($data['info']['time']) - strtotime('TODAY'),
            'remainging' => null
          ),
          'win' => 0.00075
        );
        
        /* Time Remaining */
          $args['time']['remaining'] = 1500 - $args['time']['played'];
          $args['win'] *= $args['time']['remaining'];
          
        /* Team Scores*/
          $data['teams'][0]['score'] = $this->formula_domination_score($args, $data['teams'][0], $data['teams'][1]);
          $data['teams'][1]['score'] = $this->formula_domination_score($args, $data['teams'][1], $data['teams'][0]);
          
        return $args;
      }
      private function formula_domination_score( $args, $team, $opponent ) {
        $win_gain    = $team['outcome'] ? $this->vars['gain_win'] : 0;
        $time_played = $args['time']['played'] * 0.0066;
        $score_team  = $team['score_points'] + $time_played + ( $team['outcome'] ? $args['win'] : 0);
        $score_opp   = $opponent['score_points'] + $time_played + ( $opponent['outcome'] ? $args['win'] : 0);
        
        return ( $score_team / ( $score_team + $score_opp ) ) * $this->vars['gain'] + $win_gain;
      }
      private function formula_payload( &$data ) {
        /* Team Times */
          $data['teams'][0]['time'] = $this->formula_payload_time($data['teams'][0], $data['teams'][1]);
          $data['teams'][1]['time'] = $this->formula_payload_time($data['teams'][1], $data['teams'][0]);
          
        /* Team Push Rates */
          $data['teams'][0]['pushrate'] = $data['teams'][0]['score_percentage'] / $data['teams'][0]['time'];
          $data['teams'][1]['pushrate'] = $data['teams'][1]['score_percentage'] / $data['teams'][1]['time'];
          
        /* Team Scores*/
          $data['teams'][0]['score'] = $this->formula_payload_score($data['teams'][0], $data['teams'][1]);
          $data['teams'][1]['score'] = $this->formula_payload_score($data['teams'][1], $data['teams'][0]);
      }
      private function formula_payload_time( $team, $opponent ) {
        $time = strtotime($team['score_time']) - strtotime('TODAY');
        
        if ( $team['score_percentage'] < 55 && $team['score_percentage'] < $opponent ['score_percentage'] ) $time = 720;
        
        return $time;
      }
      private function formula_payload_score( $team, $opponent ) {
        $win_gain = $team['outcome'] ? $this->vars['gain_win'] : 0;
        
        return ( $team['pushrate'] / ( $team['pushrate'] + $opponent['pushrate'])) * $this->vars['gain'] + $win_gain;
      }
    
    // Database
      private function store_player_stat( $match, $team, $player ) {
        global $wpdb;
        
        $data = array_merge($match, $team, $player);
        $wpdb->insert('dl_players', $data);
      }
      private function store_team_stat( $match, $team ) {
        global $wpdb;
        
        $data   = array_merge($match, $team);
        $result = $wpdb->insert('dl_teams', $data);
      }
      private function team_rank( $team_id ) {
        global $pxl, $wpdb;
        
        $sql  = $wpdb->prepare("SELECT ROUND(SUM(rank_gain)) FROM dl_teams WHERE team_id = %d AND season = %d AND datetime <= '%s'", $team_id, $pxl->season['number'], $this->cycle['start']);
        $rank = $wpdb->get_var($sql);
        
        return $rank += 1000;
      }
  }
}