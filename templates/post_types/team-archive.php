<?php $global = $pxl->stats->global(); ?>
<style>
  .grid{justify-content: center;}
  @media (max-width: 480px) {
    .grid__item {
      flex: 0 0 33% !important;
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
          <div class="bar__pill bar__pill--time" title="Number of Teams">
            <span class="bar__icon"><i class="fas fa-users-class"></i></span>
            <?php echo $global['teams']; ?>
          </div>
          <div class="bar__pill bar__pill--players" title="Number of Competitors">
            <span class="bar__icon"><i class="fas fa-head-vr"></i></span>
            <?php echo $global['players']; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="alignwide">
    <div class="grid grid--fifths">
      <?php pxl::loop('team'); ?>
    </div>
  </div>
  <?php pxl::paginate(); ?>
</div>