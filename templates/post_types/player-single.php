<?php
  global $wpdb;
  
  $player = new dlPlayer();
  $player->get_stats();
  
  $maps = array_column($wpdb->get_results("SELECT id, post_title FROM {$wpdb->prefix}posts WHERE post_type = 'map'"), 'post_title', 'id');
?>
<div class="content">
  <style>
    .stats{display:flex;margin:2em auto;width:400px;}
    .stats__stat{flex:0 0 33%;text-align:center;}
    .stats__stat span{display:block;}
    .stats__stat span, table th{font-family:'Oswald', sans-serif;font-weight:600;text-transform:uppercase;}
    .matches{margin:2em auto;width:50%;}
  </style>
  <div class="alignwide">
    <br>
    <div class="player player--<?php echo $player->captain ? 'captain' : 'player'; ?>">
      <div class="player__tag">
        <?php
          if ( $player->team ) printf('<span class="player__tag--team"><span>%s</span></span>', $player->team->name);
          printf('<span class="player__tag--name"><span>%s</span></span>', get_the_title());
        ?>
      </div>
      <div class="player__bot"></div>
    </div>
    
    <div class="stats">
      <div class="stats__stat">
        <span>Kills</span>
        <?php echo $player->stats['kills']; ?>
      </div>
      <div class="stats__stat">
        <span>Deaths</span>
        <?php echo $player->stats['deaths']; ?>
      </div>
      <div class="stats__stat">
        <span>K/D</span>
        <?php echo $player->stats['kd']; ?>
      </div>
    </div>
    
   <table class="matches">
      <tr>
        <th align="left">Date</th>
        <th align="center">Against</th>
        <th align="center">Map</th>
        <th align="center">K</th>
        <th align="center">D</th>
        <th align="center">HS</th>
        <th align="right">Score</th>
      </tr>
      <?php
        foreach ($player->matches as $key => $match) {
          $data = explode('=', $match->matchID);
          $date = date_create_from_format("Ymd", $data[0]); unset($data[0]);
          
          printf('
            <tr>
              <td align="left">%s</td>
              <td align="center">%s</td>
              <td align="center">%s</td>
              <td align="center">%s</td>
              <td align="center">%s</td>
              <td align="center">%s</td>
              <td align="right">%s</td>
            </tr>',
            $date->format('m-d-Y'), $match->opponent, $maps[$match->map_id], $match->kills, $match->deaths, $match->headshots, number_format($match->score)
          );
        }
      ?>
    </table>
  </div>
</div>