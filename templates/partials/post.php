<?php if ( $not_found ) : ?>
  <h3>No Posts</h3>
<?php else: ?>
  <div class="post">
    <?php if ( has_post_thumbnail() ) : ?>
      <a href="<?php the_permalink(); ?>"><?php pxl::image(array( 'w' => 370, 'h' => 150 )); ?></a>
    <?php endif; ?>
    <div class="post__header">
      <span class="date"><?php the_time('M j, Y'); ?></span>
      <h3 class="post__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
    </div>
    <div class="post__excerpt">
      <?php pxl::excerpt(); ?>
      <a href="<?php the_permalink(); ?>">Read More</a>
    </div>
  </div>
<?php endif; ?>