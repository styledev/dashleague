<?php
  global $captains;
  $captains = array_column(get_field('captains'), 'ID');
?>
<div class="grid__item team team--full">
  <div class="team__logo">
    <a href="<?php the_permalink(); ?>" class="team__title">
      <?php the_title(); ?>
    </a>
    <?php if ( has_post_thumbnail() ) : ?>
      <a href="<?php the_permalink(); ?>"><?php $image =  pxl::image(array('w' => 128, 'h' => 128)); ?></a>
    <?php endif; ?>
  </div>
  <div class="team__players">
    <ul>
      <?php pxlACF::loop('team-player', 'players') ?>
    </ul>
  </div>
</div>