<?php /* Template Name: Matches */
  wp_enqueue_script('api');
  $cycles = $pxl->stats->cycles();
  
  rsort($cycles);
  
  $maps = array(
    'ctf_coast'            => 'Coast',
    'pay_canyon'           => 'Canyon',
    'Payload_Blue_Art'     => 'Canyon',
    'pay_launchpad'        => 'Launchpad',
    'Payload_Orange_Art'   => 'Launchpad',
    'pay_abyss'            => 'Abyss',
    'dom_waterway'         => 'Waterway',
    'Domination_Yellow'    => 'Waterway',
    'cp_stadium'           => 'Stadium',
    'ControlPoint_Stadium' => 'Stadium',
    'dom_quarry'           => 'Quarry',
    'Domination_Grey'      => 'Quarry',
    'dom_volcano'          => 'Volcano',
  );
?>

<div class="content">
  <div class="matches wp-block-group alignfull">
    <div class="wp-block-group__inner-container">
      <div class="matches__container alignwide">
        <br/>
        <?php
          foreach ($cycles as $cycle) {
            if ( $cycle['cycle'] == 7 ) continue;
            
            $status = 'display';
            
            $end = DateTime::createFromFormat("Y-m-d H:i:s", $cycle['end']);
            $tz = new DateTimeZone('America/Los_Angeles');
            $end->setTimeZone($tz);
            $end->add(new DateInterval('P2D'));
            
            $query = array(
              'where' => sprintf("gs.datetime >= '%s' AND gs.datetime <= '%s'", $cycle['start'], $end->format('Y-m-d H:i:s'))
              // 'where' => "gs.matchID = '20230226=TBDi<>guh'" // for testing
            );
            
            $matches = $pxl->stats->games($query);
            rsort($matches);
            
            $count = count($matches);
            $title = "Cycle {$cycle['cycle']} – {$count} Matches";
            
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