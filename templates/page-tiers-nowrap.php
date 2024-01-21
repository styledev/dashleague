<?php /* Template Name: Tiers */
  global $pxl;
  
  $pxl->season_dates();
  
  $cycle = isset($_GET['cycle']) ? $_GET['cycle'] : $pxl->cycle;
  $tiers = $pxl->api->data_tiers(array('cycle' => $cycle, 'mmr' => TRUE));
  
  if ( !$tiers ) {
    printf("<h2>No Data for Cycle %d</h2>", $cycle);
    die;
  }
  
  $sizes = [
    'discord' => [ 'height' => 619, 'width' => 1100, 'heading_top' => 13, 'heading_size' => 48, 'gap' => 49, 'grid' => '75px 57px 54px 35px', 'sides' => 58, 'bottom' => 111, 'top' => 100, 'size' => 20 ],
    'obs' => [ 'height' => 1080, 'width' => 1920, 'heading_top' => 13, 'heading_size' => 72, 'gap' => 81, 'grid' => '150px 114px 108px 70px', 'sides' => 100, 'bottom' => 192, 'top' => 173, 'size' => 42 ],
  ];
?>
<script src="<?php echo RES ?>/js/html2canvas.min.js"></script>

<style>
  body{background-color:#111;min-height:100vh;}
  .box{display:flex;flex-direction:row-reverse;font-family:'Oswald',sans-serif;overflow:hidden;}
  
  .clone{position:relative;}
  .clone img{display:block;}
  .clone .title{color:#fff;font-weight:600;left:0;letter-spacing:-3px;position:absolute;right:0;text-align:center;white-space:nowrap;}
  
  .tiers{color:#fff;font-weight:400;display:grid;grid-template-columns:repeat(3,1fr);grid-template-rows:auto;position:absolute;}
  .tier{display: grid;}
  .tier__teams{list-style-type:none;margin:0;padding:0;}
  .tier__team{align-items:center;display:grid;justify-content:space-between;}
  .tier__team--header{align-content:end;}
  .tier__team + .tier__team{border-top:2px solid #F81EFF;}
  .tiers + canvas{padding:0;}
  .tiers .wp-block-group__inner-container{display:flex;}
  .tier span, .tier strong{flex:0 0 33%;}
  .tier span{text-align:center;}
  .tier strong{text-align:right;padding-right:6px;}
</style>

<div>
  <?php
    foreach ($sizes as $type => $args) {
      echo "
        <style>
          .box--$type{ min-height:{$args['height']}px;min-width:{$args['width']}px; height:{$args['height']}px;width:{$args['height']}px; }
          .box--$type .clone{height:{$args['height']}px;width:{$args['width']}px;}
          .box--$type .clone > img{height:{$args['height']}px!important;width:{$args['width']}px!important;}
          .box--$type .clone .title{font-size:{$args['heading_size']}px;top:{$args['heading_top']}px;}
          .box--$type .tiers{bottom:{$args['bottom']}px;font-size:{$args['size']}px;grid-column-gap:{$args['gap']}px;left:{$args['sides']}px;right:{$args['sides']}px;top:{$args['top']}px;}
          .box--$type .tier__team{grid-template-columns:{$args['grid']};}
        </style>
      ";
      
      $tiers_rows = '';
      
      if ( $tiers ) {
        foreach ($tiers as $tier => $data) {
          $teams = array();
          
          foreach ($data as $team) {
            $teams[] = sprintf('<strong>%s</strong><span>%s</span><span>%s</span><span>+</span>', $team['name'], $team['mmr'], $team['sr']);
          }
          
          $tiers_rows .= sprintf(
            '<div class="tier tier--%s">
              <div class="tier__team tier__team--header"><strong></strong><span>MMR</span><span>SR</span><span></span></div>
              <div class="tier__team">%s</div>
            </div>',
            ucwords($tier), implode('</div><div class="tier__team">', $teams)
          );
        }
      }
      
      printf(
        '
          <div class="box box--%s">
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
        $pxl->season['number'], $cycle,
        $tiers_rows,
        RES . '/images/dl-tiers-' . $type . '-' . ($_GET['style'] ?? 'a')
      );
    }
  ?>
</div>

<script>
  var boxes = document.querySelectorAll('.box');
  
  boxes.forEach((box) => {
    var clone = box.querySelector('.clone');
    
    html2canvas(clone, {
      width: clone.clientWidth,
      height: clone.clientHeight,
      scale: clone.clientWidth == 1920 ? 1 : 2,
      useCORS: true,
      imageTimeout: 0,
      allowTaint: true
    }).then(function(canvas) {
      box.appendChild(canvas);
    });
  });
</script>