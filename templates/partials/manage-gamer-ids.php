<?php
  global $wpdb;
  
  wp_enqueue_script('api');
  
  $_teams = array_column(get_posts([
    'order'          => 'ASC',
    'orderby'        => 'post_title',
    'posts_per_page' => -1,
    'post_type'      => 'team',
    'tax_query'      => [ [ 'taxonomy' => 'season', 'field' => 'slug', 'terms' => 'current', ] ]
  ]), 'ID', 'post_title');
  
  $teams = [];
  foreach ($_teams as $_team => $team_id) {
    if ( $roster = get_field('players', $team_id) ) {
      $players = array_column($roster, 'ID');
      $teams[$_team] = $players;
    }
  }
  
  $competitors = array_column(get_posts(array(
    'post_type'      => 'player',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'tax_query'      => [ [ 'taxonomy' => 'season', 'field' => 'slug', 'terms' => 'current' ] ]
  )), 'post_title', 'ID');
  fns::log($competitors);
  
  $gamerids_players = array_column($wpdb->get_results("
    SELECT LOWER(pm.meta_value) as gamer_id, p.id as player
    FROM {$wpdb->prefix}posts AS p
    JOIN {$wpdb->prefix}postmeta AS pm ON p.id = pm.post_id AND pm.meta_key LIKE 'gamer_i%' AND pm.meta_value != ''
    WHERE p.post_status = 'publish'
  ", ARRAY_A), 'player', 'gamer_id');
  
  $gamerids_partial = [];
  foreach ($gamerids_players as $gamer_id => $post_id) {
    $gamer_id = explode('-', $gamer_id);
    $gamerids_partial[$gamer_id[0]] = $post_id;
  }
  
  $query = [];
  
  $cycles = $pxl->stats->cycles();
  
  if ( !empty($cycles) ) {
    $cycle = array_shift($cycles);
    $query['where'] = sprintf("gs.datetime >= '%s'", $cycle['start']);
  }
  
  $matches    = $pxl->stats->games($query);
  $player_ids = array();
  
  foreach ($matches as $matchID => $match) {
    $games = array_reverse($match['games']);
    foreach ($games as $game) {
      $colors = array_column($game['teams'], 'players', 'color');
      
      if ( is_array($colors['red']) && is_array($colors['blue']) ) {
        $players = array_merge($colors['red'], $colors['blue']);
        
        foreach ($players as $player) {
          if ( !isset($player_ids[$player->name]) ) {
            $player_id = isset($gamerids_players[$player->id]) ? $gamerids_players[$player->id] : FALSE;
            
            if ( !$player_id ) {
              $gamer_id  = explode('-', $player->id);
              $player_id = isset($gamerids_partial[$gamer_id[0]]) ? $gamerids_partial[$gamer_id[0]] : FALSE;
            }
            
            if ( !$player_id ) $player_id = array_search($player->name, $competitors);
            
            $competitor = $player_id && isset($competitors[$player_id]) ? $competitors[$player_id] : FALSE;
            $team       = isset($teams[$player->tag]) ? $teams[$player->tag] : FALSE;
            
            $player_ids[$player->name] = array(
              'tag'        => $player->tag,
              'name'       => $player->name,
              'gamer_id'   => $player->id,
              'gamer_ids'  => [],
              'record' => [
                'id'        => $player_id,
                'name'      => $competitor,
                'matched'   => strtolower($competitor) == strtolower($player->name),
                'gamer_ids' => $player_id ? array_unique(array_filter(array(get_field('gamer_id', $player_id), get_field('gamer_id_alt', $player_id)))) : [],
                'team'      => $team && in_array($player_id, $team) ? $player->tag : FALSE,
                'team_id'   => $team && in_array($player_id, $team) ? $_teams[$player->tag] : FALSE,
                'rostered'  => $team && in_array($player_id, $team),
              ]
            );
            
            sort($player_ids[$player->name]['record']['gamer_ids']);
          }
          
          $games = array_values($match['games']);
          $player_ids[$player->name]['gamer_ids'][] = $player->id;
          $player_ids[$player->name]['gamer_ids']   = array_unique($player_ids[$player->name]['gamer_ids']);
          
          sort($player_ids[$player->name]['gamer_ids']);
          
          $player_ids[$player->name]['matches'][]   = $games[0]['matchID'];
          $player_ids[$player->name]['matches']     = array_unique($player_ids[$player->name]['matches']);
        }
      }
    }
  };
?>
<style>
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;text-align:left;}
  tbody tr:nth-child(odd){background:#eee;}
  .nofill{pointer-events:none;}
</style>

<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="list alignfull">
      <table>
        <thead>
          <tr>
            <th>Tag</th>
            <th>Name</th>
            <th width="10%">Status</th>
            <th>Gamer ID</th>
            <th>Gamer IDs</th>
            <th>Gamer IDs #</th>
            <th>Matches Played</th>
            <th>Matches</th>
          </tr>
        </thead>
        <tbody>
          <?php
            ksort($player_ids);
            fns::array_sortBy('name', $player_ids);
            fns::array_sortBy('tag', $player_ids);
            
            foreach ($player_ids as $player) {
              $player_id = FALSE;
              $status    = [];
              
              if ( !$player['record']['id'] ) {
                $status[] = '<div>Player Not Found</div>';
              }
              else if ( empty($player['record']['gamer_ids']) ) {
                $status[] = '<div>Adding Gamer ID</div>';
                update_field('gamer_id', $player['gamer_id'], $player['record']['id']);
              }
              else if ( count($player['gamer_ids']) == count($player['record']['gamer_ids']) && $player['gamer_ids'] != $player['record']['gamer_ids'] ) {
                $status[] = sprintf('<a href="%s&gamer_id=%s" class="btn btn--small btn--ghost" target="_blank">Fix Gamer ID</button>', admin_url("post.php?post={$player['record']['id']}&action=edit"), $player['gamer_id']);
              }
              else if ( !in_array($player['gamer_id'], $player['record']['gamer_ids']) ) {
                $status[] = sprintf('<a href="%s&gamer_id=%s" class="btn btn--small btn--ghost" target="_blank">Fix Gamer ID</button>', admin_url("post.php?post={$player['record']['id']}&action=edit"), $player['gamer_id']);
              }
              else if ( !$player['record']['team'] ) {
                $status[] = '<div>Not Rostered</div>';
              }
              
              if ( !$player['record']['matched'] && !empty($player['record']['name']) ) {
                $status[] = sprintf('<button class="btn btn--small btn--ghost" data-data=\'%s\'>Fix Name: %s</button>', json_encode($player, JSON_HEX_APOS), $player['record']['name']);
              }
              
              $link_team = isset($_teams[$player['tag']]) ? admin_url("post.php?post={$_teams[$player['tag']]}&action=edit") : $player['tag'];
              
              printf('
                <tr>
                  <td><a href="%s" target="_blank">%s</a></td>
                  <td><a href="%s" target="_blank">%s</a></td>
                  <td>%s</td>
                  <td>%s</td>
                  <td>%s</td>
                  <td>%dx</td>
                  <td>%d</td>
                  <td>%s</td>
                </tr>',
                $link_team,
                $player['tag'],
                admin_url("post.php?post={$player['record']['id']}&action=edit"),
                $player['name'],
                implode("\r\n", $status),
                implode("<br>", $player['gamer_ids']),
                implode("<br>", $player['record']['gamer_ids']),
                count($player['record']['gamer_ids']),
                count($player['matches']),
                implode('<br>', $player['matches']),
              );
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
          console.log(data);
          name_current.value = data.name;
          gi.el.$form.scrollIntoView();
          // name_corrected.focus();
          name_corrected.value = data.record.name
        }
      })
    });
  </script>
</div>