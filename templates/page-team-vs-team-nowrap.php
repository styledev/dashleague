<?php /* Template Name: Team vs Team */
  global $pxl;
  
  $pxl->season_dates();
  $cycle = $pxl->cycle;
  
  $teams = isset($_GET['teams']) ? $_GET['teams'] : array();
  
  if ( empty($teams) ) exit;
  
  $names = explode('-', $teams);
  $teams = get_posts(array(
    'post_type'     => 'team',
    'post_name__in' => $names,
    'orderby'       => 'post_name__in'
  ));
?>
<script src="<?php echo RES ?>/js/html2canvas.min.js"></script>

<style>
  body{background-color:#000;}
  .box{display:flex;flex-direction:row-reverse;min-height:720px;min-width:1280px;
    height:720px;width:1280px;overflow:hidden;
  }
  .clone{height:720px;position:relative;width:1280px;}
  .clone img{display:block;}
  .clone > img{height:720px!important;width:1280px!important;}
  .clone__team{position:absolute;}
  .clone__team--0{left:100px;top:100px;}
  .clone__team--1{bottom:100px;right:100px;}
  .clone__team img{height:350px;width:350px;}
  #betterdocs-ia{display:none!important;}
</style>

<div class="box">
  <div class="clone">
    <?php
      foreach ($teams as $key => $team) {
        $image = pxl::image($team->ID, array('h' => 350, 'w' => 350, 'return' => 'tag', 'srcset' => FALSE, 'retina' => false));
        printf('<div class="clone__team clone__team--%s">%s</div>', $key, $image);
      }
    ?>
    <img src="<?php echo RES ?>/images/dl-yt-base.jpg" width="1280" height="720" alt="Dl Yt Base">
  </div>
</div>

<script>
  var box = document.querySelector('.box'),
      clone = document.querySelector('.clone');
      
  html2canvas(clone, {
    width:1280,
    height:720,
    scale: 1,
    useCORS: true,
    imageTimeout: 0,
    allowTaint: true
  }).then(function(canvas) {
    box.appendChild(canvas);
  });
</script>