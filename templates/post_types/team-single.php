<?php
  wp_enqueue_script('api');
  wp_enqueue_style('swiper.min');
  wp_enqueue_script('swiper.min');
  
  $team     = get_the_title();
  $captains = get_field('captains') ? array_column(get_field('captains'), 'ID') : array();
  $players  = get_field('players') ? array_column(get_field('players'), 'post_title', 'ID') : array();
  $team_id  = get_the_ID();
  
  $matches = $pxl->stats->games(array(
    'where' => "(gs.matchID LIKE '%={$team}<%' OR gs.matchID LIKE '%>{$team}')"
    // 'where' => "t.team_id = {$team_id}",
    // 'join'  => 'JOIN dl_teams AS t ON t.game_id = gs.game_id'
  ));
  
  arsort($matches);
  
  $maps = array(
    'pay_canyon'           => 'Canyon',
    'Payload_Blue_Art'     => 'Canyon',
    'pay_launchpad'        => 'Launchpad',
    'Payload_Orange_Art'   => 'Launchpad',
    'dom_waterway'         => 'Waterway',
    'Domination_Yellow'    => 'Waterway',
    'cp_stadium'           => 'Stadium',
    'ControlPoint_Stadium' => 'Stadium',
    'dom_quarry'           => 'Quarry',
    'Domination_Grey'      => 'Quarry',
  );
  
  $stats = $pxl->stats->team($team_id);
  $logo  = pxl::image(array('w' => 100, 'h' => 100, 'return' => 'tag' ));
?>
<div class="content">
  <div class="bar wp-block-group alignfull">
    <div class="bar__container wp-block-group__inner-container">
      <div class="bar__wrapper alignwide">
        <div class="bar__pos bar__pos--left">
          <?php
            if ( $logo ) printf('<span class="bar__logo">%s</span>', $logo);
            printf('
              <div class="bar__info">
                <div class="bar__title">%s</div>
                <div class="bar__rp">
                  <div class="bar__score">%s</div>
                  <div class="bar__tag">MMR</div>
                </div>
              </div>',
              $team, ($stats ? $stats->mmr : 0)
            );
          ?>
        </div>
        <div class="bar__pos bar__pos--right">
          <div class="bar__pill bar__pill--standing">
            <span class="bar__icon"><i class="fas fa-trophy-alt"></i></span>
            <?php printf('<span>%s &nbsp;&dash;&nbsp; %s</span>', ($stats ? $stats->won : 0), ($stats ? $stats->lost : 0)); ?>
          </div>
          <div class="bar__pill bar__pill--kd">
            <span class="bar__icon"><i class="far fa-crosshairs"></i></span>
            <span><?php echo ($stats ? $stats->kd : 0); ?></span>
          </div>
          <div class="bar__pill bar__pill--time">
            <span class="bar__icon"><i class="fas fa-clock"></i></span>
            <span><?php echo ($stats ? $stats->time_played : 0); ?></span>
          </div>
          <div class="bar__pill bar__pill--players">
            <span class="bar__icon"><i class="fas fa-head-vr"></i></span>
            <?php
              printf('
                <span>%s</span>
                <span class="bar__separator">/</span>
                <span>12</span>',
                count($players),
              )
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php the_content(); ?>
  <div class="players wp-block-group alignfull">
    <div class="wp-block-group__inner-container">
      <div class="players__container alignwide">
        <div class="swiper-container">
          <div class="swiper-wrapper players">
            <?php
              foreach ($players as $id => $name) {
                $type = in_array($id, $captains) ? 'captain' : 'player';
                printf('
                  <a href="%s" class="swiper-slide player player--%s">
                    <div class="player__tag">
                      <span class="player__tag--name"><span>%s</span></span>
                    </div>
                    <div class="player__bot"></div>
                  </a>
                ', get_permalink($id), $type, $name);
              }
            ?>
          </div>
        </div>
        <div class="swiper-btn swiper-btn--prev"><i class="fad fa-chevron-double-left fa-2x"></i></div>
        <div class="swiper-btn swiper-btn--next"><i class="fad fa-chevron-double-right fa-2x"></i></div>
      </div>
    </div>
  </div>
  <div class="matches wp-block-group alignfull">
    <div class="wp-block-group__inner-container">
      <div class="matches__container alignwide">
        <div class="events__container event__container--matches-today alignwide" data-title="Matches & Scoreboards">
          <?php
            foreach ($matches as $matchID => $match) {
              $status = 'display';
              include(PARTIAL . '/match.php');
            }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  var dl, swiper;
  document.addEventListener("DOMContentLoaded", function(event) {
    swiper = new Swiper('.swiper-container', {
      grabCursor: true,
      navigation: {
        nextEl: '.swiper-btn--next',
        prevEl: '.swiper-btn--prev',
      },
      loop: true,
      loopFillGroupWithBlank: true,
      breakpoints: {
        0:   { slidesPerView: 3, slidesPerGroup: 3, spaceBetween: 0,  },
        480: { slidesPerView: 3, slidesPerGroup: 3, spaceBetween: 10, },
        780: { slidesPerView: 5, slidesPerGroup: 5, spaceBetween: 10, }
      }
    });
    
    dl = new dlAPI;
  });
</script>