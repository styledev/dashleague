<?php /* Template Name: Tiers */
  global $pxl;
  
  $pxl->season_dates();
  
  $tiers = $pxl->api->data_tiers(array('cycle' => $pxl->cycle + 1, 'mmr' => TRUE));
  
  if ( empty($tiers) ) $tiers = $pxl->api->data_tiers(array('cycle' => $pxl->cycle, 'mmr' => TRUE));
?>
<script src="<?php echo RES ?>/js/html2canvas.min.js"></script>

<style>
  body{background-color:#000;}
  #betterdocs-ia{display:none!important;}
  
  .box{
    display:flex;flex-direction:row-reverse;
    min-height:480px;min-width:760px;
    height:480px;width:760px;
    overflow:hidden;
  }
  .clone{height:480px;position:relative;width:760px;}
  .clone img{display:block;}
  .clone > img{height:480px!important;width:760px!important;}
  
  .tiers{bottom:0;color:#fff;display:grid;font-family:'Oswald',sans-serif;grid-column-gap:4em;grid-template-columns:repeat(3,1fr);left:0;padding:1em 2em;position:absolute;right:0;top:0;}
  .tier h5{color:#fff;margin-top:0;text-align:center;}
  .tier--dasher h5, .tier--dasher span{text-shadow:0 0 10px #ff007c;}
  .tier--sprinter h5, .tier--sprinter span{text-shadow:0 0 10px #fff100;}
  .tier--walker h5, .tier--walker span{text-shadow:0 0 10px #00ff0d;}
  .tier__teams{list-style-type:none;margin:0;padding:0;}
  .tier__team{display:flex;justify-content:space-between;margin: 0.30em 0;}
  .tiers + canvas{padding:0;}
  .tiers .wp-block-group__inner-container{display:flex;}
</style>

<div class="box">
  <div class="clone">
    <div class="tiers">
      <?php
        foreach ($tiers as $tier => $data) {
          $teams = array();
          
          foreach ($data as $team) {
            $teams[] = sprintf('<span>%s</span><span>%s</span>', $team['name'], $team['mmr']);
          }
          
          printf(
            '<div class="tier tier--%s">
              <h5>%s</h5>
              <ul class="tier__teams"><li class="tier__team">%s</li></ul>
            </div>',
            $tier, ucwords($tier), implode('</li><li class="tier__team">', $teams)
          );
        }
      ?>
    </div>
    <img src="<?php echo BLK ?>/dl-tiers/dl-s3-tier-bg.jpg" width="760" height="480">
  </div>
</div>

<script>
  var box   = document.querySelector('.box'),
      clone = document.querySelector('.clone');
      
  html2canvas(clone, {
    width:clone.offsetWidth,
    height:clone.offsetHeight,
    scale: 2,
    imageTimeout: 0,
    dpi: 4,
    logging: true,
    useCORS: true,
  }).then(function(canvas) {
    box.appendChild(canvas);
  });
</script>