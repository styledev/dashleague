<?php
  $global        = $pxl->stats->global();
  $season_number = $pxl->season['number'];
  
  if ( $season = get_query_var('list_season') ) {
    $parts = explode('-', $season);
    $season_number = $parts[1];
  }
?>
<style>
  .grid{justify-content: center;}
  @media (max-width: 480px) {
    .grid__item:not(.team--full) {
      flex: 0 0 33%!important;
      min-width:auto;
    }
  }
</style>
<div class="content">
  <div class="bar wp-block-group alignfull">
    <div class="bar__container wp-block-group__inner-container">
      <div class="bar__wrapper alignwide">
        <div class="bar__pos bar__pos--left">
          <div class="bar__info">
            <div class="bar__title">
              Teams
            </div>
            <div class="bar__rp">
              <div class="bar__score"><?php echo $global['teams']; ?></div>
              &nbsp;
              <div class="bar__tag bar__tag--expand">SEASON</div>
              
              <?php
                for ($i=0; $i < $pxl->season['number'] ; $i++) {
                  $s = $i+1;
                  
                  $link = $s == $pxl->season['number'] ? "/teams/" : sprintf("/teams/season-%s", $s);
                  $selected = $s == $season_number ? ' bar__score--current' : '';
                  
                  printf('<a href="%s" class="bar__score%s">%s</a>', $link, $selected, $s);
                }
              ?>
            </div>
          </div>
        </div>
        <div class="bar__pos bar__pos--right">
          <a href="#" class="bar__pill bar__pill--time" title="Number of Teams">
            <span class="bar__icon"><i class="fa-solid fa-flag"></i></span>
            <?php echo $global['teams']; ?>
          </a>
          <a href="#" class="bar__pill bar__pill--players" title="Number of Competitors">
            <span class="bar__icon"><i class="fa-solid fa-robot"></i></span>
            <?php echo $global['players']; ?>
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="alignwide">
    <?php if ( $global['teams'] ) : ?>
      <div class="grid grid--full">
        <?php pxl::loop('team-players'); ?>
      </div>
    <?php else: ?>
      <br><br>
      <div>
        <div style="text-align:center;">
          <strong>Season <?php echo $pxl->season['number']; ?></strong>
          <br>
          Team Registration ends <?php echo $pxl->season_dates['team_cut-off'] ?>
          <br><br>
          <a href="/team-registration/" class="btn btn--blue-light">Register your Team</a>
          <br><br>
          <em><i>To view past season's teams click the season number above.</i></em>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <?php pxl::paginate(); ?>
</div>