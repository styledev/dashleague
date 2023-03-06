<?php if ( $load == 'header' ) : $season = $pxl->season_dates(); ?>
<div id="menutray" style="display:none;">
  <div class="tray-inner">
    <span class="closebtn"><i class="fal fa-times fa-lg"></i></span>
    <ul>
      <li class="visible-phone">
        <?php
          if ( $social_networks = get_field('social_networks', 'options') ) {
            foreach ($social_networks as $key => $network) {
              printf('<a href="%s" target="_blank"><i class="fa-brands fa-%s"></i></a>', $network['link'], strtolower($network['icon']));
            }
          }
        ?>
      </li>
      <?php pxl::menu('mobile'); ?>
    </ul>
  </div>
</div>
<div id="container">
  <div class="navbar navbar--center navbar--smart <?php if ( is_front_page() ) echo 'navbar--transparent_at_top' ?>">
    <div class="topbar">
      <div class="topbar__area topbar__area-left">
        <ul class="navbar__menu">
          <li>
            <?php printf('<span data-area="%1$s">%1$s</span>', $season['label']) ?>
            <a href="/docs/season-structure/"><?php echo $season['value'] ?></a>
          </li>
          <?php pxl::menu('topbar-left') ?>
        </ul>
      </div>
      <div class="topbar__area topbar__area-right">
        <ul class="navbar__menu">
          <?php pxl::menu('topbar-right') ?>
          <li class="visible-desktop"><span>FOLLOW US:</span></li>
          <?php
            if ( $social_networks = get_field('social_networks', 'options') ) {
              foreach ($social_networks as $key => $network) {
                printf('<li class="hidden-phone"><a href="%s" target="_blank"><i class="fa-brands fa-%s"></i></a></li>', $network['link'], strtolower($network['icon']));
              }
            }
          ?>
          <li>
            <a href="#" class="more"><span>Menu</span> <i class="fa-solid fa-bars"></i></a></a>
          </li>
        </ul>
      </div>
    </div>
    <div class="wrapper wrapper--full navbar-wrapper">
      <div class="navbar__area navbar__area--left">
         <ul class="navbar__menu visible-desktop">
           <?php pxl::menu('main-left') ?>
         </ul>
      </div>
      <div class="navbar__area navbar__area--middle">
        <a class="navbar__logo" href="/">
          <?php echo file_get_contents(RESOURCE . '/images/logo.svg') ?>
        </a>
      </div>
      <div class="navbar__area navbar__area--right">
        <ul class="navbar__menu visible-desktop">
          <?php pxl::menu('main-right') ?>
        </ul>
      </div>
    </div>
  </div>
  <div id="main">
    <?php $pxl->template('pre-content', 'wrap'); else : $pxl->template('post-content', 'wrap'); // Content Loads Between ?>
  </div>
  <div id="footer">
    <?php $pxl->template('footer', 'wrap'); ?>
    <div class="wrapper wrapper-wide footer-wrapper">
      <ul class="footer__menu">
        <?php pxl::menu('footer'); ?>
      </ul>
      <?php pxlACF::field('p', 'copyright', 'options'); ?>
    </div>
  </div>
</div>
<?php $pxl->template('modal'); endif; ?>