<?php
  $global       = $pxl->stats->global();
  $list_players = get_query_var('list_players');
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
            <div class="bar__title">Season<?php echo $pxl->season['number'] ?></div>
            <div class="bar__rp">
              <div class="bar__score"><?php echo $global['teams']; ?></div>
              <div class="bar__tag bar__tag--expand">TEAMS</div>
            </div>
          </div>
        </div>
        <div class="bar__pos bar__pos--right">
          <a href="/teams/" class="bar__pill bar__pill--time" title="Number of Teams">
            <span class="bar__icon"><i class="fas fa-users"></i></span>
            <?php echo $global['teams']; ?>
          </a>
          <a href="/teams/players/" class="bar__pill bar__pill--players" title="Number of Competitors">
            <span class="bar__icon"><i class="fas fa-vr-cardboard"></i></span>
            <?php echo $global['players']; ?>
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="alignwide">
    <div class="grid <?php echo $list_players ? 'grid--full' : 'grid--fifths'; ?>">
      <?php pxl::loop($list_players ? 'team-players' : 'team'); ?>
    </div>
  </div>
  <?php pxl::paginate(); ?>
</div>