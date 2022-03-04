<div class="team team--<?php echo $team['color']; ?>">
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
          'ex'       => '',
          'score'    => 0,
        );
        
        foreach ($team['players'] as $key => $player) {
          $assign = '';
          
          $totals['kills']    += $player->kills;
          $totals['deaths']   += $player->deaths;
          $totals['k/d']      += 0;
          $totals['accuracy'] += 0;
          $totals['hs']       += $player->headshots;
          $totals['score']    += $player->score;
          
          $kd       = $player->deaths > 0 ? ROUND($player->kills/$player->deaths, 2) : 'inf';
          $accuracy = $player->shots > 0 && $player->shots_hit > 0 ? ROUND(($player->shots_hit/$player->shots) * 100, 1) : 0;
          
          $extras = '';
          
          if ( isset($player->counters) ) $extras = "{$player->captures}/{$player->counters}";
          else if ( isset($player->captures) ) $extras = $player->captures;
          else $extras = $player->push_time . 's';
          
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
            $player->kills, $player->deaths, $kd, "{$accuracy}%", $player->headshots,
            $extras,
            $player->score
          );
        }
      ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2">&nbsp;</td>
        <?php foreach ($totals as $key => $value) printf('<th class="%s">%s</th>', $key, $value); ?>
      </tr>
    </tfoot>
  </table>
</div>