<?php
  $team = new dlTeam();
  
  if ( $team->roster['spots'] > 0 ) :
    $int = intval($team->roster['spots']);
?>
    <div class="team-card">
      <div class="team-card__avatar">
        <a href="<?php echo $team->link ?>" target="_blank">
          <?php pxl::image(array('w' => 100, 'h' => 100)) ?>
        </a>
      </div>
      <div class="team-card__title">
        <a href="<?php echo $team->link ?>" target="_blank">
          <?php the_title(); ?>
        </a>
      </div>
      <div class="team-card__spots">
        <?php echo $team->roster['spots']; ?> spot<?php if ( $int && $int != 1 ) echo 's'; ?> open
      </div>
      <?php
        printf(
          '<a href="#" class="team-card__link ajax-link btn btn--tiny btn--blue-light" data-endpoint="team/apply" data-data=\'%s\'>Apply</a>',
          json_encode(array('team_id' => $team->id))
        );
      ?>
    </div>
<?php endif; ?>