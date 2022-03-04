<?php /* Template Name: Playoff Matches */
  wp_enqueue_script('api');
  $tiers = $pxl->stats->tiers();
  rsort($tiers);
  
  $maps = array(
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
  );
  
  $playoffs = array(
    'playoffs'  => array(
      'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-10-29 00:00:00' AND gs.datetime <= '2021-10-31 23:59:59'"
    ),
    'semifinals'  => array(
      'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-10-15 00:00:00' AND gs.datetime <= '2021-10-17 23:59:59'"
    ),
    'quarterfinals'  => array(
      'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-10-08 00:00:00' AND gs.datetime <= '2021-10-10 23:59:59'"
    ),
    'wildcard'  => array(
      'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-10-01 00:00:00' AND gs.datetime <= '2021-10-03 23:59:59'"
    ),
  );
?>

<div class="content">
  <?php the_content() ?>
  <div class="matches wp-block-group alignfull">
    <div class="wp-block-group__inner-container">
      <div class="matches__container alignwide">
        <br/>
        <?php
          foreach ($playoffs as $status => $query) {
            $action = 'for ' . ucwords($status);
            $data   = $pxl->stats->games($query);
            $count  = count($data);
            $title  = $count > 1 || $count == 0 ? 'Matches' : 'Match';
            $status = 'display';
            
            if ( $status == 'recorded' ) $data = array_reverse($data);
            
            printf('<div class="events__container event__container--%s alignwide" data-title="%s %s %s">', $status, $count, $title, $action);
              foreach ($data as $matchID => $match) include(PARTIAL . '/match.php');
            echo '</div>';
          }
        ?>
      </div>
    </div>
    <script>
      document.addEventListener("DOMContentLoaded", function(event) {
        var dl = new dlAPI;
      });
    </script>
  </div>
  <br/>
</div>