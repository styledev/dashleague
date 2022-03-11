<?php
  global $wpdb;
  
  // $player = new dlPlayer();
  $id = get_the_ID();
  
  // $team = get_field('team');
  // $maps = array_column($wpdb->get_results("SELECT id, post_title FROM {$wpdb->prefix}posts WHERE post_type = 'map'"), 'post_title', 'id');
  //
  // $players = array(
  //   'captains' => array_column(get_field('captains', $team->ID), 'ID'),
  //   'players'  => array_column(get_field('players', $team->ID), 'ID'),
  // );
  //
  // $captain  = in_array(get_the_id(), $players['captains']);
  // $rostered = in_array(get_the_id(),  $players['players']);
  //
  // if ( $rostered ) $role = $captain ? 'captain' : 'player';
  // else $role = 'player';
  
  $role = 'player';
  $rostered = FALSE;
  
  $matches = $wpdb->get_results("SELECT * FROM dl_players WHERE player_id = $id ");
  
  $stats = array(
    'kills'  => array_sum(array_column($matches, 'kills')),
    'deaths' => array_sum(array_column($matches, 'deaths')),
    'score'  => array_sum(array_column($matches, 'score')),
    'time'   => 0,
  );
  
  $stats['kd'] = $stats['deaths'] > 0 ? round($stats['kills'] / $stats['deaths'], 2) : 'N/A';
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
    <div class="player player--<?php echo $role; ?>">
      <div class="player__tag">
        <?php
          if ( $rostered ) printf('<span class="player__tag--team"><span>%s</span></span>', $team->post_title);
          printf('<span class="player__tag--name"><span>%s</span></span>', get_the_title());
        ?>
      </div>
      <div class="player__bot"></div>
    </div>
    
    <div class="stats">
      <div class="stats__stat">
        <span>Kills</span>
        <?php echo $stats['kills']; ?>
      </div>
      <div class="stats__stat">
        <span>Deaths</span>
        <?php echo $stats['deaths']; ?>
      </div>
      <div class="stats__stat">
        <span>K/D</span>
        <?php echo $stats['kd']; ?>
      </div>
    </div>
    
<!--    <table class="matches">
      <tr>
        <th align="left">Date</th>
        <th>Against</th>
        <th>Map</th>
        <th>Kills</th>
        <th>Deaths</th>
        <th align="right">Score</th>
      </tr>
      <?php
        // foreach ($matches as $key => $match) {
        //   $data = explode('-', $match->matchID);
        //   $date = date_create_from_format("Ymd", $data[0]); unset($data[0]);
        //
        //   $remove_team = array_search($team->post_title, $data);
        //   unset($data[$remove_team]);
        //   $team_name = implode('', $data);
        //
        //   printf('
        //     <tr>
        //       <td align="left">%s</td>
        //       <td align="center">%s</td>
        //       <td align="center">%s</td>
        //       <td align="center">%s</td>
        //       <td align="center">%s</td>
        //       <td align="right">%s</td>
        //     </tr>',
        //     $date->format('m-d-Y'), $team_name, $maps[$match->map_id], $match->kills, $match->deaths, number_format($match->score)
        //   );
        // }
      ?>
    </table>
     -->
  </div>
</div>