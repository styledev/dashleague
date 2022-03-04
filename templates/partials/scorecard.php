<?php
  $len   = count($players);
  $teams = array(
    'red' => array_slice($players, 0, $len / 2),
    'blue' => array_slice($players, $len / 2),
  );
?>

<div class="scorecard">
  <?php echo $match['map']; ?>
  <div class="scorecard__teams">
    <?php
      foreach ($teams as $team_color => $players) {
        echo '<div class="scorecard__team">';
          
          $score_time = empty($match['score']) ? $match['score_time'] : '';
          $score_main = empty($match['score']) ? $match['score_percentage'] . '%' : $match['score'];
          
          printf('
            <div class="scorebar scorebar--%1$s">
              <span class="scorebar__color">%1$s</span>
              <span class="scorebar__score">
                <span class="scorebar__score--time">%2$s</span>
                <span class="scorebar__score--main">%3$s</span>
              </span>
            </div>',
            $team_color, $score_time, $score_main
          );
          
          echo '
            <div class="scorebar scorebar--black">
              <span class="scorebar__name">NAME</span>
              <span class="scorebar__stat">KILLS</span>
              <span class="scorebar__stat">DEATHS</span>
              <span class="scorebar__stat">SCORE</span>
            </div>
          ';
          
          include(PARTIAL . 'scorecard-players.php');
        echo '</div>';
      }
    ?>
  </div>
</div>