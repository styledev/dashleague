<?php
  global $pxl, $wpdb;
  
  wp_enqueue_script('api');
  wp_enqueue_script('api-account');
  
  $players = array();
  
  $_players = get_posts(array(
    'post_type'      => 'player',
    'posts_per_page' => -1,
    'order'          => 'ASC',
    'orderby'        => 'title',
    'season'         => 'current',
  ));
  
  foreach ($_players as $player) {
    $team = get_field('team', $player->ID);
    $players[$player->ID] = array(
      'team' => $team ? $team->post_title : 'no team',
      'name' => $player->post_title,
    );
  }
  
  $teams = array_column(get_posts(array(
    'post_type'      => 'team',
    'posts_per_page' => -1,
    'order'          => 'ASC',
    'orderby'        => 'title',
    'season'         => 'current',
  )), 'post_title', 'ID');
  
  $boosted = $wpdb->get_results($wpdb->prepare('SELECT team, name, rank_gain FROM dl_players WHERE season = %d AND game_id LIKE \'%boost%\' ORDER BY datetime DESC', $pxl->season['number']));
?>
<style>
  select{width:100%;}
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;text-align:left;}
  tbody tr:nth-child(odd){background:#eee;}
</style>

<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="tml alignwide">
      <form class="tml inline" method="POST" data-endpoint="stats/boost" data-callback="reload" data-confirm="true" novalidate="novalidate" accept-charset="utf-8">
        <div class="tml-alerts"><ul class="tml-messages"></ul></div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="team">By Team</label>
          <select id="team" name="team" required>
            <option value="">Team Players</option>
            <?php foreach ($teams as $id => $name) printf('<option value="%s" data-team="%2$s">%2$s</option>', $id, $name); ?>
          </select>
        </div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="player">Specific Player</label>
          <select id="player" name="player">
            <option value="">Choose Player</option>
            <?php foreach ($players as $id => $player) printf('<option value="%s" data-team="%2$s">%2$s %3$s</option>', $id, $player['team'], $player['name']); ?>
          </select>
        </div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="mmr_boost">MMR Boost</label>
          <input class="tml-field" type="text" name="mmr_boost" value="" id="mmr_boost" pattern="[0-9]*" required>
        </div>
        <div class="tml-field-wrap tml-submit-wrap">
          <button name="submit" type="submit" class="tml-button btn--small">
            Boost MMR
          </button>
        </div>
      </form>
    </div>
    <div class="list alignwide">
      <br/>
      <table>
        <thead>
          <th>Team</th>
          <th>Name</th>
          <th>Boost</th>
        </thead>
        <tbody>
          <?php
            foreach($boosted as $player) {
              printf(
                '<tr><th>%s</th><td>%s</td><td>%s</td></tr>',
                $player->team, $player->name, $player->rank_gain
              );
            }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <script>
    var el = {
      'team': document.querySelector('#team'),
      'player': document.querySelector('#player'),
      'mmr_boost': document.querySelector('#mmr_boost')
    };
    
    var options = [...el.player.children], teams = false;
    
    el.team.addEventListener('change', function(e) {
      var team = e.target.selectedOptions[0].dataset.team;
      
      jQuery('#player option').prop('disabled', false).prop('hidden', false);
      
      if ( team != '' ) {
        options.forEach(function(opt) {
          if ( opt.dataset.team != team ) {
            opt.disabled = true;
            opt.hidden   = true;
          }
        });
      }
    });
  </script>
</div>