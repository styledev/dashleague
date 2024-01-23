<?php /* Template Name: Team vs Team */
  global $pxl;
  
  $pxl->season_dates();
  $cycle = $pxl->cycle;
  
  $versus = isset($_GET['teams']) ? $_GET['teams'] : array();
  
  if ( empty($versus) ) exit;
  
  $names = explode('-', $versus);
  $teams = get_posts(array(
    'post_type'     => 'team',
    'post_name__in' => $names,
    'orderby'       => 'post_name__in'
  ));
  
  $sizes = [
    'yt'  => [ 'filename' => "dl-match-{$versus}-thumbnail", 'height' => 720, 'width' => 1280, 'logo' => 350, 'team_top' => 276, 'team_a' => 165, 'team_b' => 765 ],
    'obs' => [ 'filename' => "dl-match-{$versus}", 'height' => 1080, 'width' => 1920, 'logo' => 512, 'team_top' => 406, 'team_a' => 248, 'team_b' => 1148 ],
  ]
?>
<script src="<?php echo RES ?>/js/html2canvas.min.js"></script>

<style>
  body{background-color:#111;}
  .box{display:flex;flex-direction:row-reverse;overflow:hidden;}
  
  .clone{position:relative;}
  .clone img{display:block;}
  .clone__team{position:absolute;}
  
  #betterdocs-ia{display:none!important;}
</style>

<div>
  <?php
    foreach ($sizes as $type => $args) {
      $team_logos = '';
    
      foreach ($teams as $key => $team) {
        $image = pxl::image($team->ID, array('h' => $args['logo'], 'w' => $args['logo'], 'return' => 'tag', 'srcset' => FALSE, 'retina' => false));
        $team_logos .= sprintf('<div class="clone__team clone__team--%s">%s</div>', $key, $image);
      }
      
      echo "
        <style>
          .box--$type{height:{$args['height']}px;min-height:{$args['height']}px;min-width:{$args['width']}px;width:{$args['width']}px;}
          .box--$type .clone > img{height:{$args['height']}px!important;width:{$args['width']}px!important;}
          .box--$type .clone{height:{$args['height']}px;width:{$args['width']}px;}
          .box--$type .clone__team--0{left:{$args['team_a']}px;top:{$args['team_top']}px;}
          .box--$type .clone__team--1{left:{$args['team_b']}px;top:{$args['team_top']}px;}
          .box--$type .clone__team img{height:<?php echo {$args['logo']} ?>px;width:{$args['logo']}px;}
        </style>
      ";
      
      printf(
        '
          <div class="box box--%s" data-filename="dl-match-%s">
            <div class="clone">
              %s
              <img src="%s" width="%s" height="%s" alt="DL %1$s">
            </div>
          </div>
        ',
        $type, $args['filename'],
        $team_logos,
        RES . "/images/dl-team-vs-team-{$type}.jpg",
        $args['width'], $args['height']
      );
    }
  ?>
</div>

<script>
  var boxes = document.querySelectorAll('.box');
  
  boxes.forEach((box) => {
    var clone = box.querySelector('.clone');
    
    html2canvas(clone, {
      width:clone.clientWidth,
      height:clone.clientHeight,
      scale: 1,
      useCORS: true,
      imageTimeout: 0,
      allowTaint: true
    }).then(function(canvas) {
      box.appendChild(canvas);
      
      canvas.addEventListener('click', (e) => {
        canvas.toBlob(function(blob) {
          canvas
          var a   = document.createElement('a'),
              url = URL.createObjectURL(blob);
              
          a.download = box.dataset.filename + '.png';
          a.href = url;
          a.click();
        });
      });
    });
  })
</script>