<?php
  $users = new WP_User_Query(array(
    'fields' => array('ID', 'display_name', 'user_nicename')
  ));
?>
<style>
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;text-align:left;}
  tbody tr:nth-child(odd){background:#eee;}
  .nofill{pointer-events:none;}
</style>

<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class>
      <a href="/manage/" class="btn btn--tiny<?php if ( isset($_REQUEST['new']) ) echo ' btn--ghost' ?>">All</a>
      <a href="/manage/?new" class="btn btn--tiny<?php if ( !isset($_REQUEST['new']) ) echo ' btn--ghost' ?>">New Players / Diff Discord</a>
      <br/>
      <br/>
    </div>
    <div class="list alignwide">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Discord</th>
            <th>Player</th>
            <th>Team</th>
            <th>Current?</th>
          </tr>
        </thead>
        <tbody>
          <?php
            $player_args = array('post_type' => 'player', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC');
            
            foreach ($users->get_results() as $user) {
              $found  = FALSE;
              $links  = array();
              $name   = get_user_meta($user->ID, 'first_name', TRUE);
              $pick   = get_user_meta($user->ID, 'dl_team', TRUE);
              $season = '';
              $show   = TRUE;
              $team   = array();
              
              if ( $pick ) {
                if ( $pick = get_post($pick) ) {
                  $pick = $pick->post_title;
                }
              }
              
              $player = get_posts(array_merge($player_args, array(
                'meta_key'   => 'discord_username',
                'meta_value' => html_entity_decode($user->display_name)
              )));
              
              if ( !$player ) {
                $player = get_posts(array_merge($player_args, array('s' => $name)));
                $links[] = sprintf('<a href="%s&post_title=%s&discord_username=%s" target="_blank">new</a>', admin_url('post-new.php?post_type=player'), $name, str_replace('#', '%23', $user->display_name));
              }
              else {
                $found = TRUE;
                
                if ( count($player) == 1 ) {
                  $teams = get_posts(array(
                    'post_type' => 'team',
                    'season' => 'current',
                    'meta_query' => array(
                      array(
                        'key'     => 'players',
                        'value'   => '"' . $player[0]->ID . '"',
                        'compare' => 'LIKE'
                      )
                    )
                  ));
                  
                  foreach ($teams as $t) {
                    $edit_link = get_edit_post_link($t->ID);
                    $team[] = sprintf('<a href="%s" target="_blank">%s</a>', $edit_link, $t->post_title);
                  }
                  
                  if ( $seasons = get_the_terms($player[0]->ID, 'season')) {
                    $seasons = array_column($seasons, 'slug');
                    if ( !in_array('current', $seasons) ) $season = sprintf('<a href="%s" target="_blank">Update Season</a>', get_edit_post_link($player[0]->ID));
                  }
                }
              }
              
              foreach ($player as $p) {
                $edit_link = get_edit_post_link($p->ID);
                
                if ( !$found ) $edit_link .= sprintf('&post_title=%s&discord_username=%s', $name, str_replace('#', '%23', $user->display_name));
                
                $links[] = sprintf('<a href="%s" target="_blank">%s</a>', $edit_link, $p->post_title);
              }
              
              $links = implode(', ', $links);
              $team = count($team) > 1 ? $pick . ' = ' . implode(', ', $team) : '';
              
              if ( isset($_REQUEST['new']) && $found ) $show = FALSE;
              
              if ( $show ) {
                printf('
                  <tr>
                    <td>%s</td>
                    <td><a href="%s" target="_blank">%s</a></td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                  </tr>',
                  $user->ID,
                  get_edit_user_link($user->ID),
                  $name,
                  $user->display_name,
                  $links,
                  $team,
                  $season
                );
              }
            }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>