<?php
  $teams = get_posts([
    'order'          => 'ASC',
    'orderby'        => 'post_title',
    'posts_per_page' => -1,
    'post_type'      => 'team',
    'tax_query'      => [ [ 'taxonomy' => 'season', 'field' => 'slug', 'terms' => 'current', ] ]
  ]);
  
  $stats = [
    'Total'          => 0,
    'Registered'     => 0,
    'Not Registered' => 0,
    'Gamer IDs'      => 0,
    'Missing'        => 0,
    'Issues'         => 0,
    'Alts'           => 0,
  ];
?>
<style>
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;text-align:left;}
  tbody tr:nth-child(odd){background:#eee;}
  .nofill{pointer-events:none;}
  .reverse{display:flex;flex-direction:column-reverse;}
  .stats {
    background-color:var(--dark-xlight);
    display: flex;
    justify-content: center;
    padding:1em;
    margin-bottom:2em;
  }
  .stats strong{color:var(--dark);}
  .stats > div{margin:0 2em;}
  
  @media(max-width:480px){
    .stats{flex-wrap:wrap;justify-content:space-between;}
    .stats > div{flex:1 0 48%;margin:0.25em 0;text-align:center;}
  }
</style>

<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="reverse alignwide">
      <div class="list">
        <table>
          <thead>
            <tr>
              <th>Team</th>
              <th width="1%">Player</th>
              <th>Gamer ID</th>
              <th>Gamer ID Alt</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
              foreach ($teams as $team) {
                $team = new dlTeam($team);
                $total = count($team->roster['players']) + count($team->roster['error']);
                
                printf('<tr><th rowspan="%d">%s</th>', $total + 1, $team->name);
                
                if ( $team->roster ) {
                  $team->roster['captains'] = array_column($team->roster['captains'], 'name', 'ID');
                  $team->roster['players']  = array_column($team->roster['players'], 'name', 'ID');
                  $team->roster['error']    = array_column($team->roster['error'], 'name', 'ID');
                  
                  foreach ($team->roster as $type => $players) {
                    if ( empty($players) || !in_array($type, ['captains', 'players', 'error']) ) continue;
                    
                    asort($players);
                    
                    if ( $players ) {
                      foreach ($players as $player_id => $player_name) {
                        if ( $type == 'players' && isset($team->roster['captains'][$player_id]) ) continue;
                        
                        $stats['Total']++;
                        
                        $gamer_id     = get_post_meta($player_id, 'gamer_id', TRUE);
                        $gamer_id_alt = get_post_meta($player_id, 'gamer_id_alt', TRUE);
                        
                        $issues = [];
                        
                        if ( $type == 'error' ) {
                          $issues[] = 'No User';
                          $stats['Not Registered']++;
                        }
                        else $stats['Registered']++;
                        
                        if ( $type != 'error' ) {
                          if ( empty($gamer_id) ) {
                            $issues[] = 'ID Missing';
                            $stats['Missing']++;
                          }
                          elseif ( !strpos($gamer_id, '-') ) {
                            $issues[] = 'ID Issue';
                            $stats['Issues']++;
                          }
                        }
                        
                        if ( $gamer_id_alt && !strpos($gamer_id_alt, '-') ) {
                          $issues[] = 'ALT Issue';
                          $stats['Issues']++;
                        }
                        
                        if ( $gamer_id ) $stats['Gamer IDs']++;
                        if ( $gamer_id_alt ) $stats['Alts']++;
                        
                        $issues = implode("<br/>", $issues);
                        
                        printf(
                          '
                            <tr>
                              <td class="type-%s">%s</td>
                              <td>%s</td>
                              <td>%s</td>
                              <td><span style="color:red;"><small>%s</small></span></td>
                            </tr>
                          ',
                          $type,
                          $player_name,
                          $gamer_id ?? '<span style="color:red">NOT ADDED YET</span>',
                          $gamer_id_alt ?? '',
                          $issues
                        );
                      }
                    }
                  }
                  
                  // if ( $team->name == 'KNHT' ) fns::put($team->roster);die;
                }
                
                echo '</tr>';
              }
            ?>
          </tbody>
        </table>
      </div>
      <div class="team">
        <div class="stats">
          <?php
            foreach ($stats as $name => $total) {
              printf('<div><strong>%s</strong><br/>%s</div>', $name, $total);
            }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>