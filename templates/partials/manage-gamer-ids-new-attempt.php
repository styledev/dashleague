<?php
  wp_enqueue_script('api');
  
  $competitors = array_change_key_case(array_column(get_posts(array(
    'post_type'      => 'player',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
  )), 'ID', 'post_title'), CASE_LOWER);
  
  $matches = $pxl->stats->games();
  $players = array();
  
  foreach ($matches as $matchID => $match) {
    $games = array_reverse($match['games']);
    foreach ($games as $game) {
      $colors = array_column($game['teams'], 'players', 'color');
      
      if ( is_array($colors['red']) && is_array($colors['blue']) ) {
        $_players = array_merge($colors['red'], $colors['blue']);
        
        foreach ($_players as $player) {
          if ( !isset($players[$player->name]) ) {
            $players[$player->name] = array(
              'tag'  => $player->tag,
              'name' => $player->name,
              'ids' => array()
            );
          }
          
          $players[$player->name]['ids'][] = $player->id;
          $players[$player->name]['ids'] = array_unique($players[$player->name]['ids']);
        }
      }
    }
  }
?>
<style>
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;text-align:left;}
  tbody tr:nth-child(odd){background:#eee;}
  .nofill{pointer-events:none;}
</style>

<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="list alignwide">
      <table>
        <thead>
          <tr>
            <th>Tag</th>
            <th>Name</th>
            <th>Status</th>
            <th>#</th>
            <th>IDs</th>
          </tr>
        </thead>
        <tbody>
          <?php
            global $wpdb;
            
            ksort($players);
            fns::array_sortBy('tag', $players);
            
            $status = '';
            
            $player_list = array(
              ''
            )
            
            foreach ($players as $player) {
              $data = $pxl->stats->player_ids($player['ids']);
              
              if ( isset($data[0]) ) {
                fns::put("Multiple Players Found for Gamer IDs");
                fns::put($player);
                fns::put($data);
              }
              else if ( $data ) {
                fns::put("Player Found");
                $player = array_merge($player, $data);
                fns::put($player);
              }
              else {
                fns::put("NO PLAYER FOUND");
                fns::put($player);
              }
              
              // if ( $data ) {
              //   $diff = array_diff($player['ids'], $data->gamer_ids);
              //
              //   if ( !empty($diff) ) {
              //     fns::put($data);
              //     fns::put($diff);
              //
              //     // $player_check
              //   }
              // }
              // else {
              //
              // }
              
              
              // $player_id = FALSE;
              //
              // foreach ($player['ids'] as $id) {
              //   $player_id = $wpdb->get_var($wpdb->prepare("
              //     SELECT p.id
              //     FROM {$wpdb->prefix}postmeta AS pm
              //     JOIN {$wpdb->prefix}posts AS p ON p.id = pm.post_id
              //     WHERE pm.meta_value = '%s' AND p.post_title = '%s'
              //   ", $id, $player['name']));
              // }
              //
              // if ( !$player_id ) {
              //   $player_name = strtolower($player['name']);
              //   $player_id   = isset($competitors[$player_name]) ? $competitors[$player_name] : FALSE;
              //
              //   if ( $player_id ) {
              //     $player_gamer_ids = array();
              //     foreach ($player['ids'] as $id) $player_gamer_ids[] = array('field_60f8add53e5be' => $id);
              //     update_field('gamer_ids', $player_gamer_ids, $player_id);
              //     $status = 'Saved';
              //   }
              //   else $status = sprintf('No Match <button class="btn btn--small btn--ghost" data-data=\'%s\'>Change</button>', json_encode($player));
              // }
              //
              // printf('
              //   <tr>
              //     <td>%s</td>
              //     <td>%s</td>
              //     <td>%s</td>
              //     <td>%dx</td>
              //     <td>%s</td>
              //   </tr>',
              //   $player['tag'], $player['name'], $status, count($player['ids']), implode("<br>", $player['ids'])
              // );
            }
          ?>
        </tbody>
      </table>
    </div>
    <div class="tml alignwide">
      <br>
      <form class="tml inline" method="POST" data-init="init" data-endpoint="stats/gamer" data-callback="reload" data-confirm="true" novalidate="novalidate" accept-charset="utf-8">
        <div class="tml-alerts"><ul class="tml-messages"></ul></div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="tag">Team</label>
          <input class="tml-field nofill" type="text" name="tag" value="" id="tag" required>
        </div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="name_current">Name</label>
          <input class="tml-field nofill" type="text" name="name_current" value="" id="name_current" required>
        </div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="name_corrected">Correction</label>
          <input class="tml-field" type="text" name="name_corrected" value="" id="name_corrected" required>
        </div>
        <div class="tml-field-wrap tml-submit-wrap">
          <button name="submit" type="submit" class="tml-button btn--small">
            Change Name
          </button>
        </div>
      </form>
    </div>
  </div>
  <script>
    var gi;
    
    document.addEventListener("DOMContentLoaded", function(event) {
      gi = new dlAPI;
      
      var list           = document.querySelector('.list'),
          tag            = document.querySelector('#tag'),
          name_current   = document.querySelector('#name_current'),
          name_corrected = document.querySelector('#name_corrected');
          
      list.addEventListener('click', function(e) {
        if ( e.target.tagName === 'BUTTON' ) {
          var data = JSON.parse(e.target.dataset.data);
          tag.value = data.tag;
          name_current.value = data.name;
          gi.el.$form.scrollIntoView();
          name_corrected.focus();
        }
      })
    });
  </script>
</div>