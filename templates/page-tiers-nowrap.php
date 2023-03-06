<?php /* Template Name: Tiers */
  global $pxl;
  
  $pxl->season_dates();
  
  $cycle = isset($_GET['cycle']) ? $_GET['cycle'] : $pxl->cycle;
  $tiers = $pxl->api->data_tiers(array('cycle' => $cycle, 'mmr' => TRUE));
  
  if ( !$tiers ) printf("<h2>Tiers are not set yet for cycle %d</h2>", $cycle);
?>
<script src="<?php echo RES ?>/js/html2canvas.min.js"></script>

<style>
  body{background-color:#000;}
  #betterdocs-ia{display:none!important;}
  
  .box{
    display:flex;flex-direction:row-reverse;
    min-height:428px;min-width:760px;
    height:428px;width:760px;
    overflow:hidden;
  }
  .clone{height:428px;position:relative;width:760px;}
  .clone img{display:block;}
  .clone > img{height:428px!important;width:760px!important;}
  
  .tiers{bottom:0;color:#fff;display:grid;font-family:'Oswald',sans-serif;grid-column-gap:4em;grid-template-columns:repeat(3,1fr);left:0;padding:1em 2em;position:absolute;right:0;top:0;}
  .tier h5{color:#fff;margin-top:0;text-align:center;}
  .tier--dasher h5, .tier--dasher span{text-shadow:0 0 10px #ff007c;}
  .tier--sprinter h5, .tier--sprinter span{text-shadow:0 0 10px #fff100;}
  .tier--walker h5, .tier--walker span{text-shadow:0 0 10px #00ff0d;}
  .tier__teams{list-style-type:none;margin:0;padding:0;}
  .tier__team{display:flex;justify-content:space-between;margin: 0.30em 0;}
  .tiers + canvas{padding:0;}
  .tiers .wp-block-group__inner-container{display:flex;}
  
  .tiers {
    bottom: 0;
    color: #fff;
    display: grid;
    font-family: 'Oswald',sans-serif;
    grid-column-gap: 57px;
    grid-template-columns: repeat(3,1fr);
    left: 0;
    padding: 20px 60px;
    position: absolute;
    right: 0;
    top: 110px;
  }
  .tier__team{
    display: grid;
    grid-column-gap: 0;
    grid-template-columns: 64px 57px 54px;
    margin:0.25em 0;
  }
  
  .tier span, .tier strong{flex:0 0 33%;}
  .tier span{text-align:center;}
  .tier strong{text-align:right;padding-right:6px;}
</style>

<div class="box">
  <div class="clone">
    <div class="tiers">
      <?php
        foreach ($tiers as $tier => $data) {
          $teams = array();
          
          foreach ($data as $team) {
            $teams[] = sprintf('<strong>%s</strong><span>%s</span><span>%s</span>', $team['name'], $team['mmr'], $team['sr']);
          }
          
          printf(
            '<div class="tier tier--%s">
              <div class="tier__team">%s</div>
            </div>',
            ucwords($tier), implode('</div><div class="tier__team">', $teams)
          );
        }
      ?>
    </div>
    <img src="<?php echo BLK ?>/dl-tiers/dl-s5-tier-bg.jpeg" width="760" height="428">
    <?php /* ?><img src="<?php echo BLK ?>/dl-tiers/dl-s3-tier-bg.jpg" width="760" height="428"> */?>
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