<?php
  if ( $seasons = get_terms(['taxonomy' => 'season']) ) {
    $seasons = array_column($seasons, 'term_id', 'slug');
    $current = $seasons['current'];
  }
  
  $players_all = array_column(get_posts([
    'post_type'      => 'player',
    'posts_per_page' => -1
  ]), 'ID', 'post_name');
  
  $teams_current = get_posts([
    'post_type'      => 'team',
    'fields'         => 'ids',
    'posts_per_page' => -1,
    'tax_query'      => [ [ 'taxonomy' => 'season', 'field' => 'slug', 'terms' => 'current', ] ]
  ]);
  
  $teams_not = array_column(get_posts([
    'post_type'      => 'team',
    // 'fields'         => 'ids',
    'posts_per_page' => -1,
    'tax_query'      => [ [ 'taxonomy' => 'season', 'field' => 'slug', 'terms' => 'current', 'operator' => 'NOT IN' ] ]
  ]), 'ID', 'post_name');
  
  $players_current = [];
  $players_not     = [];
  
  foreach ($teams_current as $team_id) {
    if ( $players = get_field('players', $team_id) ) {
      foreach ($players as $key => $player) {
        if ( isset($players_all[$player->post_name]) ) unset($players_all[$player->post_name]);
        
        $players_current[$player->post_name] = $player->ID;
      }
    }
  }
  
  foreach ($teams_not as $team_name => $team_id) {
    if ( $players = get_field('players', $team_id) ) {
      $count = count($players);
      
      foreach ($players as $key => $player) {
        if ( isset($players_current[$player->post_name]) ) unset($players[$key]);
        else $players_not[$player->post_name] = $player->ID;
      }
      
      $players = array_values($players);
      
      if ( $count !== count($players) ) update_field('players', $players, $team_id);
    }
  }
?>
<style>
  .team+.team{margin-top:3em;}
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;}
  th{white-space:nowrap;}
  td[align="center"]{text-align:center;}
  tbody tr:nth-child(odd){background:#eee;}
  .nofill{pointer-events:none;}
  tfoot tr td{background-color:#ffd5d5!important;color:red;font-weight:bold;font-size:larger;text-align:left;}
</style>
<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="list alignwide">
      <h2><?php echo count($players_not) ?> Remove from Current Season Check</h2>
      <div class="team">
        <table>
          <thead>
            <th>Player</th>
            <th>Post ID</th>
            <th>Action</th>
          </thead>
          <tbody>
            <?php
              foreach ($players_all as $player_name => $player_id) {
                printf('<tr><th>%s</th><td>%s</td><td>', $player_name, $player_id);
                
                if ( $ps = get_the_terms($player_id, 'season') ) {
                  $ps = array_column($ps, 'term_id', 'slug');
                  if ( in_array($current, $ps) ) {
                    unset($ps['current']);
                    echo "Removed from current Season";
                    if ( wp_set_post_terms($player_id, $ps, 'season', FALSE) ) {
                      echo "<div style='color:red;'>FAILED</div>";
                    }
                  }
                  else echo "Not Playing this Season";
                }
                else echo 'No Seasons Listed';
                
                echo '</td>';
              }
            ?>
          </tbody>
        </table>
      </div>
      
      <h2><?php echo count($players_current) ?> Add to Current Season Check</h2>
      <div class="team">
        <table>
          <thead>
            <th>Player</th>
            <th>Post ID</th>
            <th>Action</th>
          </thead>
          <tbody>
            <?php
              foreach ($players_current as $player_name => $player_id) {
                printf('<tr><th>%s</th><td>%s</td><td>', $player_name, $player_id);
                
                if ( $ps = get_the_terms($player_id, 'season') ) {
                  $ps = array_column($ps, 'term_id', 'slug');
                  if ( !in_array($current, $ps) ) {
                    echo "Adding to CURRENT";
                    if ( !wp_set_post_terms($player_id, $current, 'season', TRUE) ) {
                      echo "<div style='color:red;'>FAILED</div>";
                    }
                  }
                  else echo "CURRENT";
                }
                else {
                  echo 'No Seasons Listed';
                  echo "<br>Adding to CURRENT";
                  if ( !wp_set_post_terms($player_id, $current, 'season', TRUE) ) {
                    echo "<div style='color:red;'>FAILED</div>";
                  }
                }
                
                echo '</td>';
              }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>