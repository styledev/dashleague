<?php if ( !class_exists('dlMMR') ) {
  class dlMMR {
    function __construct() {}
      
    // API
      public function match( $match ) {
        global $pxl, $wpdb;
        
        $ranks_match   = !empty($match[0]['teams']) ? $match[0]['teams'] : $match[1]['teams'];
        $ranks_players = array_fill_keys(array_column($ranks_match, 'team_id'), array());
        $ranks_teams   = array_fill_keys(array_column($ranks_match, 'team_id'), 0);
        
        $i = 1; $count = count($match);
        foreach ($match as $mode => $data) {
          if ( $data['info']['game_id'] == 'forfeit' ) {
            $winner_mmr_avg = $wpdb->get_var($wpdb->prepare('SELECT ROUND(AVG(rank_gain)) FROM dl_teams WHERE team_id = %d AND season = %d AND rank_gain > 0', $data['winner'], $pxl->season['number']));
            
            $teams = array_column($data['teams'], null, 'team_id');
            
            $team  = $teams[$data['winner']]; unset($teams[$data['winner']]);
            $teams = array_values($teams);
            
            $opponent = array_pop($teams);
            
            $team  = array_merge($team, array(
              'season'      => $pxl->season['number'],
              'name'        => get_the_title($team['team_id']),
              'opponent_id' => $opponent['team_id'],
              'opponent'    => get_the_title($opponent['team_id']),
              'deaths'      => 0,
              'kills'       => 0,
              'score'       => 0,
              'rank_gain'   => $winner_mmr_avg,
              'notes'       => 'Win from forfeit'
            ));
            
            $this->store_team_stat($data['info'], $team);
            break;
          }
          else if ( $data['info']['game_id'] == 'double-forfeit' ) {
            foreach ($data['teams'] as $key => $team) {
              $opponent = $key == 0 ? $data['teams'][1] : $data['teams'][0];
              
              $team  = array_merge($team, array(
                'season'      => $pxl->season['number'],
                'name'        => get_the_title($team['team_id']),
                'opponent_id' => $opponent['team_id'],
                'opponent'    => get_the_title($opponent['team_id']),
                'deaths'      => 0,
                'kills'       => 0,
                'score'       => 0,
                'rank_gain'   => 0,
                'notes'       => 'Double forfeit'
              ));
              
              $this->store_team_stat($data['info'], $team);
            }
            
            break;
          }
          else if ( $data['info']['game_id'] == 'map-forfeit' ) {
            $winner_mmr_avg = $wpdb->get_var($wpdb->prepare('SELECT ROUND(AVG(rank_gain)) FROM dl_teams WHERE team_id = %d AND season = %d AND rank_gain > 0', $data['winner'], $pxl->season['number']));
            $ranks_teams[$data['winner']] += $winner_mmr_avg;
            $i++;
            continue;
          }
          
          $data['info']['season'] = $pxl->season['number'];
          if ( strlen($data['info']['time']) < 8 ) $data['info']['time'] = "00:{$data['info']['time']}";
          $this->extract($data); // Extract and store Player IDs, Ranks, and Score
          $this->match_id($data);
          
          if ( $i === 1 ) { // Set updated Rank
            foreach ($data['teams'] as $team) $ranks_players[$team['team_id']] = $team['ranks'];
          }
          else {
            foreach ($data['teams'] as $key => $team) {
              $team_diff = array_diff_key($team['ranks'], $ranks_players[$team['team_id']]);
              if ( !empty($team_diff) ) $ranks_players[$team['team_id']] = $ranks_players[$team['team_id']] + $team_diff;
              $data['teams'][$key]['ranks'] = array_intersect_key($ranks_players[$team['team_id']], $data['teams'][$key]['ranks']);
            }
          }
          
          $gains = $this->mmr_rank_gain($data);
          
          foreach ($gains as $team_id => $rank) {
            $ranks_teams[$team_id] += array_sum($rank);
            
            foreach ($rank as $id => $value) $ranks_players[$team_id][$id] += $value;
          }
          
          foreach ($data['teams'] as $key => $team) {
            $team    = array_merge($team, array('deaths' => 0, 'kills' => 0, 'score' => 0));
            $players = $team['players'];
            
            // Unset data
              unset($team['ids']);
              unset($team['players']);
              unset($team['ranks']);
              unset($team['scores']);
              
            // Opponent ID
              $team['opponent_id'] = $key == 0 ? $data['teams'][1]['team_id'] : $data['teams'][0]['team_id'];
              $team['opponent']    = $key == 0 ? $data['teams'][1]['name'] : $data['teams'][0]['name'];
              
            if ( isset($team['score_time']) && strlen($team['score_time']) < 8 ) $team['score_time'] = "00:{$team['score_time']}";
            
            foreach ($players as $player) {
              $id_rank = explode('|', $player['player_id']);
              $player['player_id']   = $id_rank[0];
              $player['rank_gain']   = $gains[$team['team_id']][$player['player_id']];
              $player['opponent_id'] = $team['opponent_id'];
              $player['opponent']    = $team['opponent'];
              
              $team['deaths'] += $player['deaths'];
              $team['kills']  += $player['kills'];
              $team['score']  += $player['score'];
              
              $this->store_player_stat($data['info'], $team, $player);
            }
            
            // Only calculate team rank gain on last map to get average
              if ( $i === $count ) {
                $team['rank_gain'] = $ranks_teams[$team['team_id']] / $count;
                $data['teams'][$key]['rank_gain'] = $team['rank_gain'];
              }
              
            $this->store_team_stat($data['info'], $team);
          }
          
          $match[$mode] = $data;
          
          $i++;
        }
        
        $dir = wp_upload_dir();
        
        ob_start();
          echo "RANKS TEAMS\n";
          fns::put($ranks_teams);
          echo "RANKS PLAYERS\n";
          fns::put($ranks_players);
          echo "\nMMR DATA\n";
          fns::put($match);
        $content = str_replace(array('<pre>', '</pre>'), '', ob_get_clean());
        
        file_put_contents(
          "{$dir['basedir']}/mmr/{$data['info']['matchID']}.txt",
          $content,
          FILE_APPEND
        );
        
        return $ranks_players;
      }
      private function extract( &$data ) {
        foreach ($data['teams'] as $key => $team) {
          if ( $team['outcome'] == 1 ) $data['winner'] = $team['team_id'];
          
          $data['teams'][$key]['name'] = get_the_title($team['team_id']);
          
          $ids_ranks = array_column($team['players'], 'player_id');
          
          $ids   = array();
          $ranks = array_map(function($v) use(&$ids) {
            $id_rank = explode('|', $v);
            $ids[] = $id_rank[0];
            return $id_rank[1];
          }, $ids_ranks);
          
          $data['teams'][$key]['ids']    = $ids;
          $data['teams'][$key]['ranks']  = array_combine($ids, $ranks);
          $data['teams'][$key]['scores'] = array_combine($ids, array_column($team['players'], 'score'));
        }
      }
      
    // MMR
      public function mmr_rank_gain( $match ) {
        $match['args']    = array('win' => 70, 'var' => 100, 'multiplier' => 3);
        $match['ranks']   = array(
          'all'  => array(),
          'comp' => array(),
          'norm' => array(),
          'avg'  => NULL, 'std' => NULL, 'min' => NULL
        );
        $match['scores']  = array(
          'all'  => array(),
          'comp' => array(),
          'norm' => array(),
          'avg'  => NULL, 'std' => NULL, 'min' => NULL
        );
        
        // Team Specific
          foreach ($match['teams'] as $team => $args) {
            unset($match['teams'][$team]['players']); // for dev purposes
            
            $args['ranks']  = array('all' => $args['ranks']);
            $args['scores'] = array('all' => $args['scores']);
            
            $this->mmr_rank_adjustment($args);
            
            $match['ranks']['all']  = array_merge($match['ranks']['all'], $args['ranks']['all']);
            $match['scores']['all'] = array_merge($match['scores']['all'], $args['scores']['all']);
            
            $this->mmr_avg_std_min($args, 'ranks');
            $this->mmr_avg_std_min($args, 'scores');
            $match['teams'][$team] = $args;
          }
          
          $match['teams'][0]['ranks']['norm'] = $this->mmr_normalize($match['teams'][0], 'ranks', $match['teams'][1]);
          $match['teams'][1]['ranks']['norm'] = $this->mmr_normalize($match['teams'][1], 'ranks', $match['teams'][0]);
          
          $this->mmr_cross($match);
          
        // Combined
          $this->mmr_avg_std_min($match, 'ranks');
          $this->mmr_avg_std_min($match, 'scores');
          
          $match['ranks']['norm']  = $this->mmr_normalize($match, 'ranks');
          $match['ranks']['comp']  = $this->mmr_compress($match, 'ranks');
          $match['scores']['norm'] = $this->mmr_normalize($match, 'scores');
          $match['scores']['comp'] = $this->mmr_compress($match, 'scores');
          
        // Modifiers
          $this->mmr_modifiers($match);
          
        // Calculate
          $this->mmr_results($match);
          
        return $match['gains'];
      }
      private function mmr_avg_std_min( &$arr, $type ) {
        $arr["{$type}"]['avg'] = array_sum($arr[$type]['all']) / count($arr[$type]['all']);
        $arr["{$type}"]['std'] = $this->mmr_std_dev($arr[$type]['all']);
        $arr["{$type}"]['min'] = $arr["{$type}"]['avg'] / -$arr["{$type}"]['std'];
      }
      private function mmr_compress( $arr, $type, $min_max = FALSE ) {
        $items = array();
        
        if ( !$min_max ) $temp  = array_merge($arr[$type]['norm'], array($arr[$type]['min']));
        
        $min   = $min_max ? $arr[$type]['min'] : min($temp);
        $max   = $min_max ? $arr[$type]['max'] : max($arr[$type]['norm']);
        
        foreach ($arr[$type]['norm'] as $value) $items[] = ($value - $min) / ($max - $min);
        
        return $items;
      }
      private function mmr_cross( &$match ) {
        $match['cross'] = array(
          'norm' => array_merge($match['teams'][0]['ranks']['norm'], $match['teams'][1]['ranks']['norm']),
        );
        
        $match['cross']['min'] = min(array_merge($match['cross']['norm'], array($match['teams'][0]['ranks']['min'], $match['teams'][1]['ranks']['min'])));
        $match['cross']['max'] = max(array_merge($match['cross']['norm'], array(abs($match['teams'][0]['ranks']['min']), abs($match['teams'][1]['ranks']['min']))));
        
        $match['cross']['rank'] = $this->mmr_compress($match, 'cross', 'min_max');
      }
      private function mmr_modifiers( &$match ) {
        $match['modifiers'] = array(0 => null, 1 => null, 2 => array(), 3 => array());
        
        // Mod #0
          $match['modifiers'][0] = $match['teams'][1]['ranks']['avg'] / $match['teams'][0]['ranks']['avg'];
          
        // Mod #1
          $match['modifiers'][1] = $match['teams'][0]['ranks']['avg'] / $match['teams'][1]['ranks']['avg'];
          
        // Mod #2
          $ranks_scores = array();
          foreach ($match['scores']['comp'] as $n => $score) $ranks_scores[$n] = $score / $match['ranks']['comp'][$n];
          
          $ranks_score_sum = array_sum($ranks_scores);
          foreach ($ranks_scores as $value) $match['modifiers'][2][] = $value / $ranks_score_sum;
          
        // Mod #3
          foreach ( $match['cross']['rank'] as $value ) $match['modifiers'][3][] = sqrt(1 / $value);
      }
      private function mmr_normalize( $arr, $type, $opposing = FALSE ) {
        $items = array();
        $avg   = $opposing ? $opposing[$type]['avg'] : $arr[$type]['avg'];
        $std   = $opposing ? $opposing[$type]['std'] : $arr[$type]['std'];
        
        foreach ($arr[$type]['all'] as $value) $items[] = ($value - $avg) / $std;
        
        return $items;
      }
      private function mmr_rank_adjustment( &$arr ) {
        $adjusted = array();
        $total    = array_sum($arr['ranks']['all']); // Total team rank
        $players  = count($arr['ranks']['all']) - 1;
        
        foreach ($arr['ranks']['all'] as $n => $player_rank) {
          $avg        = ($total - $player_rank) / $players;                     // Average of team's other players
          $std        = $this->mmr_std_dev($arr['ranks']['all'], $player_rank); // Team's standard deviation
          $adjusted[] = ($avg - $player_rank) > $std ? round($avg) : $player_rank;
        }
        
        $arr['ranks']['all'] = $adjusted;
      }
      private function mmr_results( &$match ) {
        $gains  = array_fill_keys(array_column($match['teams'], 'team_id'), array());
        $player = 0;
        
        foreach ($match['teams'] as $team_number => $team) {
          $team_mod = $match['modifiers'][$team_number];
          
          foreach ($team['ids'] as $id) {
            $gain = round(($match['args']['var'] * $match['modifiers'][2][$player] * $match['modifiers'][3][$player] * $team_mod * $match['args']['multiplier']));
            
            if ( $match['winner'] === $team['team_id'] ) $gain += $match['args']['win'];
            
            $gains[$team['team_id']][$id] = $gain;
            
            $player++;
          }
        }
        
        $match['gains'] = $gains;
      }
      private function mmr_std_dev( $arr, $player_rank = FALSE) {
        $default = $player_rank ? 0.4 : 0.316;
        $size    = $player_rank ? count($arr) - 1 : count($arr);
        $mu      = $player_rank ? (array_sum($arr) - $player_rank) / $size : array_sum($arr) / $size;
        $ans     = 0;
        
        foreach ($arr as $elem) $ans += pow(($elem - $mu), 2);
        
        $std = sqrt($ans / $size);
        
        return $std == 0 ? $default : $std;
      }
      
    // Helpers
      private function match_id( &$data ) {
        if ( isset($data['info']['matchID']) ) return;
        
        $date = date_create_from_format("F d Y", implode(' ', $data['info']['date'])); unset($data['info']['date']);
        
        $data['info']['datetime'] = $date->format("Y-m-d 00:00:00");
        $data['info']['matchID']  = $date->format("Ymd");
        
        $teams = array_column($data['teams'], 'name'); sort($teams);
        
        $data['info']['matchID'] .= "={$teams[0]}<>{$teams[1]}";
      }
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
  }
}