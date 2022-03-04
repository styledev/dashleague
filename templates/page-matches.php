<?php /* Template Name: Matches */
  wp_enqueue_script('api');
  $tiers = $pxl->stats->tiers();
  rsort($tiers);
  
  $maps = array(
    'pay_canyon'           => 'Canyon',
    'Payload_Blue_Art'     => 'Canyon',
    'pay_launchpad'        => 'Launchpad',
    'Payload_Orange_Art'   => 'Launchpad',
    'dom_waterway'         => 'Waterway',
    'Domination_Yellow'    => 'Waterway',
    'cp_stadium'           => 'Stadium',
    'ControlPoint_Stadium' => 'Stadium',
    'dom_quarry'           => 'Quarry',
    'Domination_Grey'      => 'Quarry',
  );
?>

<div class="content">
  <div class="matches wp-block-group alignfull">
    <div class="wp-block-group__inner-container">
      <div class="matches__container alignwide">
        <br/>
        <?php
          foreach ($tiers as $tier) {
            if ( $tier['cycle'] == 7 ) continue;
            
            $status = 'display';
            
            $query = array(
              'where' => sprintf("gs.recorded IS NOT NULL AND gs.datetime >= '%s' AND gs.datetime <= '%s'", $tier['start'], $tier['end'])
            );
            
            $matches = $pxl->stats->games($query);
            rsort($matches);
            $count = count($matches);
            $title = "Cycle {$tier['cycle']} – {$count} Matches";
            
            printf('<div class="events__container event__container--matches-today alignwide" data-title="%s">', $title);
              foreach ($matches as $matchID => $match) {
                $status = 'display';
                include(PARTIAL . '/match.php');
              }
            echo '</div><br/>';
          }
        ?>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener("DOMContentLoaded", function(event) {
      var dl = new dlAPI;
    });
  </script>
</div>