<div class="team team--<?php echo isset($team['color']) ? $team['color'] : 'gray'; ?>">
  <div class="team__bar">
    <?php 
      printf('
        <span class="team__name">%s</span>
        <span class="team__time">%s</span>
        <span class="team__score">%s</span>',
        $team['name'], (isset($team['round_time']) ? $team['round_time'] : ''), $team['score']
      );
    ?>
  </div>
  <table>
    <thead>
      <tr>
        <th class="name" colspan="2">NAME</th>
        <th>K</th>
        <th>D</th>
        <th>K/D</th>
        <th>A</th>
        <th>HS</th>
        <th>ex</th>
        <th class="score">Score</th>
      </tr>
    </thead>
    <tbody>
      <?php
        global $wpdb;
        
        $totals = array(
          'kills'    => 0,
          'deaths'   => 0,
          'k/d'      => 0,
          'accuracy' => 0,
          'hs'       => 0,
          'ex'       => 0,
          'score'    => 0,
        );
        
        $key = -1;
        foreach ($team['players'] as $i => $player) {
          $key++;
          $assign = '';
          
          $totals['kills']    += $player->kills;
          $totals['deaths']   += $player->deaths;
          $totals['k/d']      += 0;
          $totals['accuracy'] += 0;
          $totals['score']    += $player->score;
          
          if ( isset($player->headshots) ) $totals['hs'] += $player->headshots;
          
          $kd = $player->deaths > 0 ? ROUND($player->kills/$player->deaths, 2) : 'inf';
          
          $accuracy = 0;
          if ( isset($player->shots) && isset($player->shots_hit) ) $accuracy = $player->shots > 0 && $player->shots_hit > 0 ? ROUND(($player->shots_hit/$player->shots) * 100, 1) : 0;
          
          $extras = '';
          
          if ( isset($player->counters) ) {
            if ( $totals['ex'] === 0 ) $totals['ex'] = array(0, 0);
            
            $extras = "{$player->captures}/{$player->counters}";
            $totals['ex'][0] += $player->captures;
            $totals['ex'][1] += $player->counters;
          }
          else if ( isset($player->ctf_captures) ) {
            if ( $totals['ex'] === 0 ) $totals['ex'] = array(0, 0);
            
            $extras = "{$player->ctf_captures}/{$player->ctf_returns}";
            $totals['ex'][0] += $player->ctf_captures;
            $totals['ex'][1] += $player->ctf_returns;
          }
          else if ( isset($player->captures) ) {
            $extras = $player->captures;
            $totals['ex'] += $player->captures;
          }
          else if ( isset($player->push_time) ) {
            $extras = $player->push_time . 's';
            $totals['ex'] += $player->push_time;
          }
          
          printf('
            <tr>
              <th class="pos">%s</th>
              <td class="name"><span>%s</span>%s</td>
              <td>%s</td>
              <td>%s</td>
              <td>%s</td>
              <td>%s</td>
              <td>%s</td>
              <td>%s</td>
              <td class="score">%s</td>
            </tr>',
            $key + 1,
            "{$player->tag} {$player->name}", $assign,
            $player->kills, $player->deaths, $kd, ( $accuracy ? "{$accuracy}%" : ''), (isset($player->headshots) ? $player->headshots : ''),
            $extras,
            $player->score
          );
        }
      ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2">&nbsp;</td>
        <?php
          foreach ($totals as $key => $value) {
            if ( is_array($value) ) $value = sprintf("%s/%s", $value[0], $value[1]);
            
            printf('<th class="%s">%s</th>', $key, $value);
            
          }
        ?>
      </tr>
    </tfoot>
  </table>
</div>