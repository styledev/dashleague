<?php /* Template Name: Tiers */
  global $pxl;
  
  $pxl->season_dates();
  
  $cycle  = isset($_GET['cycle']) ? $_GET['cycle'] : $pxl->cycle;
  $season = isset($_GET['season']) ? $_GET['season'] : $pxl->season['number'];
  $tiers  = $pxl->api->data_tiers(array('cycle' => $cycle, 'mmr' => TRUE));
  
  if ( !$tiers ) {
    printf("<h2>No Data for Cycle %d</h2>", $cycle);
    die;
  }
  
  $tiers_last = $cycle > 1 ? $pxl->api->data_tiers(array('cycle' => ($cycle - 1), 'mmr' => TRUE)) : FALSE;
  
  if ( $tiers_last ) $tiers_last = array_values($tiers_last);
  
  $sizes = [
    'hd'      => [
      'stats'        => FALSE,
      'height'       => 1020,
      'width'        => 684,
      'heading_top'  => 290,
      'heading_size' => 48,
      'gap'          => 0,
      'grid'         => '1fr',
      'sides'        => 41,
      'bottom'       => 80,
      'top'          => 430,
      'size'         => 28,
    ],
    'discord' => [
      'stats'        => TRUE,
      'height'       => 619,
      'width'        => 1100,
      'heading_top'  => 8,
      'heading_size' => 48,
      'gap'          => 49,
      'grid'         => '75px 57px 54px 35px',
      'sides'        => 58,
      'bottom'       => 111,
      'top'          => 100,
      'size'         => 20
    ],
    'obs'     => [
      'stats'        => TRUE,
      'height'       => 1080,
      'width'        => 1920,
      'heading_top'  => 13,
      'heading_size' => 72,
      'gap'          => 81,
      'grid'         => '150px 114px 108px 70px',
      'sides'        => 100,
      'bottom'       => 192,
      'top'          => 173,
      'size'         => 42
    ],
  ];
?>
<script src="<?php echo RES ?>/js/html2canvas.min.js"></script>

<style>
  body{background-color:#111;min-height:100vh;}
  .box{display:flex;flex-direction:row-reverse;font-family:'Oswald',sans-serif;overflow:hidden;}
  
  .tiers *{line-height:120%;}
  
  .clone{position:relative;}
  .clone img{display:block;}
  .clone .title{color:#fff;font-weight:600;left:0;letter-spacing:-3px;position:absolute;right:0;text-align:center;white-space:nowrap;}
  
  .tiers{color:#fff;font-weight:400;display:grid;grid-template-columns:repeat(3,1fr);grid-template-rows:auto;position:absolute;}
  .tier{display: grid;}
  .tier__teams{list-style-type:none;margin:0;padding:0;}
  .tier__team{align-items:center;display:grid;justify-content:space-between;}
  
  .box--discord .tier__team + .tier__team,
  .box--obs .tier__team + .tier__team{border-top:2px solid #F81EFF;}
  .tiers + canvas{padding:0;}
  .tiers .wp-block-group__inner-container{display:flex;}
  .tier span, .tier strong{flex:0 0 33%;}
  .tier span{text-align:center;}
  
  .tier strong{text-align:right;padding-right:6px;}
  
  .box--hd .tier strong{text-align:center;}
  .box--hd .tier strong span{display:inline-block;text-align:center;width:30px;}
  .box--hd .tier strong span:empty{display:none;}
</style>

<div>
  <?php
    foreach ($sizes as $type => $args) {
      $tiers_rows = '';
      
      if ( $tiers ) {
        $tiers_index = array_keys($tiers);
        
        foreach ($tiers as $tier => $data) {
          $index = array_search($tier, $tiers_index);
          $teams = array();
          
          $teams_current = array_keys(array_column($data, NULL, 'name'));
          
          if ( $tiers_last ) {
            $up         = $index == 0 ? FALSE : array_column($tiers_last[$index-1], NULL, 'name');
            $down       = $index == 2 ? FALSE : array_column($tiers_last[$index+1], NULL, 'name');
            $tier_last  = array_column($tiers_last[$index], NULL, 'name');
            $teams_last = array_keys($tier_last);
          }
          
          $diff = $tiers_last ? array_diff($teams_current, $teams_last) : [];
          
          foreach ($data as $pos => $team) {
            if ( in_array($team['name'], $diff) ) {
              if ( $up && isset($up[$team['name']]) ) $change = "&darr;";
              elseif ( $down && isset($down[$team['name']]) ) $change = "&uarr;";
              else $change = '';
            }
            else if ( $tiers_last ) {
              $pos_last = array_search($team['name'], array_keys($tier_last));
              
              if ( $pos < $pos_last ) $change = '+';
              elseif ($pos > $pos_last ) $change = '-';
              else $change = '';
            }
            else $change = '';
            
            if ( $args['stats'] ) {
              $teams[] = sprintf('<strong>%s</strong><span>%s</span><span>%s</span><span>%s</span>', $team['name'], $team['mmr'], $team['sr'], $change);
            }
            else {
              $teams[] = sprintf('<strong>%s <span>%s</span></strong>', $team['name'], $change);
            }
          }
          
          $tiers_rows .= sprintf(
            '<div class="tier tier--%s">
              %s
              <div class="tier__team">%s</div>
            </div>',
            ucwords($tier),
            $args['stats'] ? '<div class="tier__team tier__team--header"><strong></strong><span>MMR</span><span>SR</span><span></span></div>' : '',
            implode('</div><div class="tier__team">', $teams)
          );
        }
      }
      
      $styles = "
        <style>
          .box--$type{ min-height:{$args['height']}px;min-width:{$args['width']}px;height:{$args['height']}px;width:{$args['width']}px; }
          .box--$type .clone{height:{$args['height']}px;width:{$args['width']}px;}
          .box--$type .clone > img{height:{$args['height']}px!important;width:{$args['width']}px!important;}
          .box--$type .clone .title{font-size:{$args['heading_size']}px;top:{$args['heading_top']}px;}
          .box--$type .tiers{bottom:{$args['bottom']}px;font-size:{$args['size']}px;grid-column-gap:{$args['gap']}px;left:{$args['sides']}px;right:{$args['sides']}px;top:{$args['top']}px;}
          .box--$type .tier__team{grid-template-columns:{$args['grid']};}
        </style>
      ";
      
      printf(
        '
          <div class="box box--%s">
            %s
            <div class="clone">
              <div class="title">STANDINGS S%s C%s</div>
              <div class="tiers">
                %s
              </div>
              <img src="%s.jpg">
            </div>
          </div>
        ',
        $type,
        $styles,
        $season, $cycle,
        $tiers_rows,
        RES . '/images/dl-tiers-' . $type . '-' . ($_GET['style'] ?? 'a')
      );
    }
  ?>
  
  
</div>

<script>
  var boxes = document.querySelectorAll('.box');

  // boxes.forEach((box) => {
  //   var clone = box.querySelector('.clone');
  //
  //   html2canvas(clone, {
  //     width: clone.clientWidth,
  //     height: clone.clientHeight,
  //     scale: 1,
  //     useCORS: true,
  //     imageTimeout: 0,
  //     allowTaint: true
  //   }).then(function(canvas) {
  //     box.appendChild(canvas);
  //   });
  // });
</script>