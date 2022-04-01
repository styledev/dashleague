<div class="grid__item team">
  <div class="team__logo">
    <a href="<?php the_permalink(); ?>" class="team__title">
      <?php the_title(); ?>
    </a>
    <?php if ( has_post_thumbnail() ) : ?>
      <a href="<?php the_permalink(); ?>"><?php pxl::image(array( 'w' => 256, 'h' => 256 )); ?></a>
    <?php endif; ?>
  </div>
</div>