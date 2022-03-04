<?php
  global $pxl;
  
  if ( !isset($pxl->cycle) ) $pxl->season_dates();
  
  $tiers = $pxl->stats->data_tiers(array('cycle' => $pxl->cycle + 1, 'mmr' => TRUE));
  if ( empty($tiers) ) $tiers = $pxl->stats->data_tiers(array('cycle' => $pxl->cycle, 'mmr' => TRUE));
?>
<div id="<?php echo $block['id'] ?>" class="tiers_block">
  <style>
    .tiers_block{margin-top:2em;}
    .tiers__wrapper{background-color:#333;background-image:url('<?php echo BLK ?>/dl-tiers/dl-s3-tier-bg.jpg');background-repeat:no-repeat;background-size:cover;box-sizing:border-box;height:100%;padding:1em 2em;position:relative;border-radius:4px;}
    .tiers__wrapper:before{background-color:rgba(0,0,0,0.2);bottom:0;content:'';left:0;position:absolute;right:0;top:0;z-index:1;}
    .tiers__content{display:grid;grid-template-columns:repeat(3,1fr);grid-column-gap:4em;font-family:'Oswald',sans-serif;position:relative;z-index:10;}
    .tier h5{margin-top:0;text-align:center;}
    .tier--dasher h5, .tier--dasher span{text-shadow:0 0 10px #ff007c;}
    .tier--sprinter h5, .tier--sprinter span{text-shadow:0 0 10px #fff100;}
    .tier--walker h5, .tier--walker span{text-shadow:0 0 10px #00ff0d;}
    .tier__teams{list-style-type:none;margin:0;padding:0;}
    .tier__team{display:flex;justify-content:space-between;}
    .tiers + canvas{padding:0;}
    .tiers .wp-block-group__inner-container{display:flex;}
    @media(max-width:760px) {
      .tiers__wrapper{padding:1em;}
      .tiers__content{grid-column-gap:2em;}
    } 
  </style>
  <div class="tiers">
    <div class="tiers__wrapper">
      <div class="tiers__content">
        <?php
          foreach ($tiers as $tier => $data) {
            $teams = array();
            
            foreach ($data as $team) $teams[] = sprintf('<span>%s</span><span>%s</span>', $team->name, $team->mmr);
            
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
    </div>
  </div>
</div>