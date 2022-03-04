<div class="content">
  <hr class="spacer">
  <h1 class="h2"><?php the_title(); ?></h1>
  <div class="details"><?php the_date() ?></div>
  <hr class="spacer">
  <?php the_content(); ?>
  <div class="share">
    <hr>
    <div class="share__inner">
      <?php pxl::share(array('facebook','twitter','linkedin','email')); ?>
    </div>
  </div>
  <hr class="spacer">
</div>
