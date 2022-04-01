<?php
  global $wpdb;
  
  wp_enqueue_script('imask.min');
  wp_enqueue_script('api');
  wp_enqueue_script('api-manage');
  
  $pxl->season_dates();
  $cycles = $wpdb->get_results($wpdb->prepare("SELECT cycle, start, end FROM dl_tiers WHERE season = %d GROUP BY cycle ORDER BY cycle ASC", $pxl->season['number']));
  $cycle  = array_pop($cycles);
  $cycle_results = $wpdb->get_results($wpdb->prepare("SELECT name, team_id, sum(rank_gain) + 1000 as mmr FROM dl_teams WHERE season = %d AND datetime <= '%s' GROUP BY name ORDER BY mmr DESC", $pxl->season['number'], $cycle->end));
  $cycle_results = array_chunk($cycle_results, 11);
?>
<style>
  select{width:100%;}
  .tml.inline .tml-field-wrap{flex: 0 0 33%;}
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;text-align:left;}
  tbody tr:nth-child(odd){background:#eee;}
</style>

<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="tml alignwide">
      <h5>New Tiers for Cycle <?php echo $cycle->cycle + 1 ?></h5>
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
            Generate Cycle <?php echo $cycle->cycle + 1 ?> Tiers
          </button>
        </div>
      </form>
      <br/>
    </div>
    <div class="list alignwide">
      <?php
        foreach ($cycle_results as $tier => $teams) {
          switch ($tier) {
            case 0: $tier = 'Dasher'; break;
            case 1: $tier = 'Sprinter'; break;
            default: $tier = 'Walker'; break;
          }
          
          echo '<table><thead><th width="33%" class="center-text">Tier</th><th width="33%" class="center-text">Team</th><th width="33%" class="center-text">MMR</th></thead><tbody>';
            
            printf('<tr><th rowspan="%d" class="center-text">%s</th></tr>', count($teams) + 1, $tier);
            
            foreach ($teams as $key => $team) {
              printf('<tr><td>&nbsp;%s</td><td class="center-text">%s</td></tr> ', $team->name, $team->mmr);
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