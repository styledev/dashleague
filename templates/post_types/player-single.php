<?php
  global $wpdb;
  
  $player = new dlPlayer();
  $player->get_stats();
  $logo = $player->team ? pxl::image($player->team->id, array('w' => 100, 'h' => 100, 'return' => 'tag' )) : '';
  $maps = array_column($wpdb->get_results("SELECT id, post_title FROM {$wpdb->prefix}posts WHERE post_type = 'map'"), 'post_title', 'id');
  $team = $player->team ? "{$player->team->name} " : '';
?>
<div class="content">
  <style>
    .stats{display:flex;margin:2em auto;width:400px;}
    .stats__stat{flex:0 0 33%;text-align:center;}
    .stats__stat span{display:block;}
    .stats__stat span, table th{font-family:'Oswald', sans-serif;font-weight:600;text-transform:uppercase;}
    .matches{margin:2em auto;width:50%;}
    
    table{border-collapse:collapse;width:100%;}
    th,td{border:1px solid #ccc;padding:0.25rem;}
    thead{background-color:#000;color:#fff;}
    thead th{border-color:#000;text-align:center;}
    tbody tr:nth-child(odd){background:#eee;}
    tfoot th{background-color:var(--wp--preset--color--vivid-cyan-blue) !important;border-color:var(--wp--preset--color--vivid-cyan-blue) !important;color:#fff;}
    .scroll{overflow:scroll;}
    
    @media(max-width:480px) {
      .date{display:none;}
    }
  </style>
  <div class="alignwide">
    <div class="bar__container wp-block-group__inner-container">
      <div class="bar__wrapper alignwide">
        <div class="bar__pos bar__pos--left">
          <?php
            if ( $logo ) printf('<span class="bar__logo">%s</span>', $logo);
            $captain = $player->captain ? '<span class="server">Captain</span> ' : '';
            
            printf('
              <div class="bar__info">
                <div class="bar__title">%s%s</div>
                <div class="bar__rp">
                  <div class="bar__score">%s</div>
                  <div class="bar__tag">K/D</div>
                </div>
                <div class="bar__servers">%s<span class="server">%s</span></div>
              </div>',
              $team, get_the_title(), $player->stats['kd'], $captain, $player->server
            );
          ?>
        </div>
        <div class="bar__pos bar__pos--right">
        </div>
      </div>
    </div>
    
    <h2>All Time Stats</h2>
    <?php if ( $player->stats['score'] ) : ?>
      <table>
        <thead>
          <tr>
            <th width="20%">Win Ratio (maps)</th>
            <th width="20%">Kills</th>
            <th width="20%">Deaths</th>
            <th width="20%">K/D</th>
            <th width="20%">Score</th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <th><?php echo ROUND(($player->stats['wins'] / count($player->matches)) * 100, 2) ?>%</th>
            <th><?php echo number_format($player->stats['kills']); ?></th>
            <th><?php echo number_format($player->stats['deaths']); ?></th>
            <th><?php echo $player->stats['kd']; ?></th>
            <th><?php echo number_format($player->stats['score']); ?></th>
          </tr>
        </tfoot>
      </table><br>
    <?php endif; ?>
    
    <?php
      $season = FALSE;
      
      $table_header = '
        <tr>
          <th width="15%" align="center" class="date">Date</th>
          <th width="10%" align="center">VS</th>
          <th width="15%" align="center">Map</th>
          <th width="10%" align="center">Wins</th>
          <th width="10%" align="center">K</th>
          <th width="10%" align="center">D</th>
          <th width="10%" align="center">K/D</th>
          <th width="10%" align="center">HS</th>
          <th width="10%" align="right">Score</th>
        </tr>
      ';
      
      $matches = count($player->matches);
      
      foreach ($player->matches as $key => $match) {
        if ( empty($match->matchID) || !$match->score ) continue;
        
        if ( $season !== $match->season ) {
          if ( $season ) {
            printf(
              '
                </tbody>
                <tfoot>
                  <th class="date"></th>
                  <th colspan="2"></th>
                  <th align="center">%s%%</th>
                  <th align="center">%s</th>
                  <th align="center">%s</th>
                  <th align="center">%s</th>
                  <th align="center">%s</th>
                  <th align="right">%s</th>
                </tfoot>
                </table></div></br>
              ',
              ROUND(($stats['wins'] / $stats['total']) * 100, 2),
              number_format($stats['kills']),
              number_format($stats['deaths']),
              ROUND($stats['kills'] / $stats['deaths'], 2),
              number_format($stats['hs']),
              number_format($stats['score'])
            );
          }
          
          $season = $match->season;
          $stats = [ 'wins' => 0, 'total' => 0, 'kills' => 0, 'deaths' => 0, 'k/d' => 0, 'hs' => 0, 'score' => 0 ];
          
          printf(
            '
              <h2>Season %s</h2>
              <div class="scroll">
              <table>
                <thead>%s</thead>
                <tbody>
            ',
            $season,
            $table_header
          );
        }
        
        $data = explode('=', $match->matchID);
        
        if ( !isset($data[1]) ) {
          $data = explode('-', $match->matchID);
          $data = [ $data[0], "{$data[1]}-{$data[2]}"];
        }
        
        $date = date_create_from_format("Ymd", $data[0]); unset($data[0]);
        
        if ( $match->outcome ) $stats['wins'] += 1;
        $stats['total']  += 1;
        $stats['kills']  += $match->kills;
        $stats['deaths'] += $match->deaths;
        $stats['hs']     += $match->headshots;
        $stats['score']  += $match->score;
        
        printf('
          <tr>
            <td align="center" class="date">%s</td>
            <td align="center">%s</td>
            <td align="center">%s</td>
            <td align="center">%s</td>
            <td align="center">%s</td>
            <td align="center">%s</td>
            <td align="center">%s</td>
            <td align="center">%s</td>
            <td align="right">%s</td>
          </tr>',
          $date->format('m-d-Y'),
          $match->opponent,
          $maps[$match->map_id] ?? "missing?",
          $match->outcome ? 1 : 0,
          $match->kills,
          $match->deaths,
          ( $match->deaths == 0 ? 0 : ROUND($match->kills/$match->deaths, 2)),
          $match->headshots,
          number_format($match->score)
        );
        
        if ( $matches == $key + 1 ) {
          printf(
            '
              </tbody>
              <tfoot>
                <th class="date"></th>
                <th colspan="2"></th>
                <th align="center">%s%%</th>
                <th align="center">%s</th>
                <th align="center">%s</th>
                <th align="center">%s</th>
                <th align="center">%s</th>
                <th align="right">%s</th>
              </tfoot>
              </table></div></br>
            ',
            ROUND(($stats['wins'] / $stats['total']) * 100, 2),
            number_format($stats['kills']),
            number_format($stats['deaths']),
            ROUND($stats['kills'] / $stats['deaths'], 2),
            number_format($stats['hs']),
            number_format($stats['score'])
          );
        }
      }
    ?>
  </div>
</div>