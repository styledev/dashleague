<?php
  global $wpdb;
  
  wp_enqueue_script('imask.min');
  wp_enqueue_script('api');
  wp_enqueue_script('api-manage');
  
  $pxl->season_dates();
  
  $cycles  = $pxl->api->tool_cycles();
  $cycle   = $cycles[array_key_last($cycles)];
  $current = $pxl->api->data_tiers(array('cycle' => $cycle['num'], 'mmr' => TRUE));
  $tiers   = $pxl->api->tool_tiers($cycle, $current);
?>
<style>
  select{width:100%;}
  .tml.inline .tml-field-wrap{flex: 0 0 33%;}
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;text-align:left;}
  tbody tr:nth-child(odd){background:#eee;}
  .tiers{display:flex;flex:1 0 100%;justify-content:space-between;}
  
  .cols{display:grid;grid-template-columns:repeat(3,33%);}
  .cols span {text-align:center;}
</style>

<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="tml alignwide">
      <h5>Current Tiers for Cycle <?php echo $cycle['num'] ?></h5>
    </div>
    <div class="list alignwide">
      <table>
        <thead>
          <th>
            <div class="cols">
              <span>Dasher</span>
              <span>MMR</span>
              <span>SR</span>
            </div>
          </th>
          <th>
            <div class="cols">
              <span>Sprinter</span>
              <span>MMR</span>
              <span>SR</span>
            </div>
          </th>
          <th>
            <div class="cols">
              <span>Walker</span>
              <span>MMR</span>
              <span>SR</span>
            </div>
          </th>
        </thead>
        <tbody>
          <tr>
            <?php
              foreach ($current as $tier => $tier_teams) {
                echo '<td>';
                foreach ($tier_teams as $team) {
                  printf(
                    '
                      <div class="cols">
                        <span>%s</span>
                        <span>%s</span>
                        <span>%s</span>
                      </div>
                    ',
                    $team['name'],
                    $team['mmr'],
                    $team['sr']
                  );
                }
                echo '</td>';
              }
            ?>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="tml alignwide">
      <h5>New Tiers for Cycle <?php echo $cycle['num'] + 1 ?></h5>
      <form class="tml inline" method="POST" data-init="init" data-endpoint="tool/tiers" data-confirm="true" novalidate="novalidate" accept-charset="utf-8">
        <div class="tml-alerts"><ul class="tml-messages"></ul></div>
        
        <div class="tml-field-wrap">
          <label class="tml-label" for="dateStart">Start</label>
          <input class="tml-field" type="text" name="dateStart" value="" id="dateStart" placeholder="YYYY-MM-DD" mask="0000-00-00" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" required>
        </div>
        
        <div class="tml-field-wrap">
          <label class="tml-label" for="dateEnd">End</label>
          <input class="tml-field" type="text" name="dateEnd" value="" id="dateEnd" placeholder="YYYY-MM-DD" mask="0000-00-00" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" required>
        </div>
        
        <div class="tml-field-wrap tml-submit-wrap">
          <button name="submit" type="submit" class="tml-button btn--small">
            Generate Cycle <?php echo $cycle['num'] + 1 ?> Tiers
          </button>
        </div>
        
        <?php if ( empty($tiers) ) : ?>
          <div class="tiers">
            <div class="tml-field-wrap">
              <label class="tml-label" for="tier_dasher">Dasher</label>
              <select id="tier_dasher" name="tiers[0][]" size="22" multiple>
                <?php foreach ($teams as $team_id => $team_name) printf('<option value="%1$s|%2$s">%1$s</option>', $team_name, $team_id); ?>
              </select>
            </div>
            <div class="tml-field-wrap">
              <label class="tml-label" for="tier_sprinter">Sprinter</label>
              <select id="tier_sprinter" name="tiers[1][]" size="22" multiple>
                <?php foreach ($teams as $team_id => $team_name) printf('<option value="%1$s|%2$s">%1$s</option>', $team_name, $team_id); ?>
              </select>
            </div>
            <div class="tml-field-wrap">
              <label class="tml-label" for="tier_walker">Dasher</label>
              <select id="tier_walker" name="tiers[2][]" size="22" multiple>
                <?php foreach ($teams as $team_id => $team_name) printf('<option value="%1$s|%2$s">%1$s</option>', $team_name, $team_id); ?>
              </select>
            </div>
          </div>
        <?php endif; ?>
      </form>
      <br/>
    </div>
    
    <div class="list alignwide">
      <?php
        foreach ($tiers as $tier => $teams) {
          switch ($tier) {
            case 0: $tier = 'Dasher'; break;
            case 1: $tier = 'Sprinter'; break;
            default: $tier = 'Walker'; break;
          }
          
          echo '<table>
            <thead>
              <tr>
                <th width="25%" class="center-text">Tier</th>
                <th width="25%" class="center-text">Team</th>
                <th width="25%" class="center-text">MMR</th>
                <th width="25%" class="center-text">SR</th>
              </tr>
            </thead>
            <tbody>';
            
            printf('<tr><th rowspan="%d" class="center-text">%s (%d)</th></tr>', count($teams) + 1, $tier, count($teams));
            
            foreach ($teams as $key => $team) {
              printf('
                <tr>
                  <td>&nbsp;%s</td>
                  <td class="center-text">%s</td>
                  <td class="center-text">%s</td>
                </tr> ',
                $team['name'],
                $team['mmr'],
                $team['sr']
              );
            }
            
          echo '</tbody></table><br/>';
        }
      ?>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      var masks = document.querySelectorAll('input[mask]');
      
      masks.forEach((masking) => {
        IMask(masking, { mask: masking.getAttribute('mask') });
      });
    });
  </script>
</div>