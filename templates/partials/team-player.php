<?php global $captains;
  $captain = in_array(get_the_ID(), $captains);
?>
<li class="team__player team__player--<?php echo $captain ? 'captain' : 'player' ?>">
  <a href="<?php echo get_permalink() ?>">
    <?php the_title(); ?>
  </a>
</li>