<?php
  $team       = array_keys($match['teams']);
  $team_a     = $match['teams'][$team[0]];
  $team_b     = $match['teams'][$team[1]];
  $scores     = array_column($match['teams'], 'score', 'name'); arsort($scores);
  $winner     = array_search(max($scores), $scores);
  $recorded   = empty($match['recorded']) ? '' : '<span class="event__recorded" title="MMR Approved"><i class="fas fa-check-circle"></i></span>';
  $scoreboard = is_null($status) || $status == 'process' || $status == 'display';
  $action     = $scoreboard ? '<button class="btn btn--ghost" data-action="show" data-target="games" data-toggle="btn--ghost active" data-text="Scoreboards" data-active="Close"></button>' : '<div class="event__approved">Approved</div>';
  $collapsed  = $status == 'process' ? '' : ' games--collapsed';
?>

<div class="event">
  <?php
    printf('
      <div class="event__vs">
        <a href="%s" target="_blank">%s</a>
        <span>VS</span>
        <a href="%s" target="_blank">%s</a>
      </div>
      <div class="event__details">
        <div class="event__title">
          <span class="event__team event__team--%6$s">%s</span>
          <span class="event__score event__score--%s">[%s]</span>
          <span>vs</span>
          <span class="event__team event__team--%9$s">%s</span>
          <span class="event__score event__score--%s">[%s]</span>
        </div>
        <div class="event__date" data-date="%s">
          %s%s
        </div>
      </div>
      <div class="event__actions">
        %s
      </div>',
      $team_a['link'], $team_a['logo'],
      $team_b['link'], $team_b['logo'],
      $team_a['name'], ($team_a['name'] == $winner ? 'winner' : 'loser'), $team_a['score'],
      $team_b['name'], ($team_b['name'] == $winner ? 'winner' : 'loser'), $team_b['score'],
      $match['datetime']->format('Y-m-d H:i:s'),
      $match['datetime']->format('F jS, Y'),
      $recorded,
      $action
    );
  ?>
  <div class="games<?php echo $collapsed; ?>"><?php
      $action = FALSE;
      
      foreach ($match['games'] as $game) {
        if ( $scoreboard && !in_array($game['game_id'], array('forfeit', 'double-forfeit', 'map-forfeit')) ) {
          echo '<div class="game">';
            
            printf('<div class="details__notes">%s</div>', $game['notes']);
            
            printf('
              <div class="game__details">
                <span class="details__map">Map: %s</span>
                <span class="details__winner">Winner: %s</span>
                <span class="details__time">Time: %s</span>
              </div>',
              $maps[$game['map']], ucwords($game['winner']), $game['time']
            );
            
            echo '<div class="game__scores">';
              foreach ($game['teams'] as $team) include(PARTIAL . '/match-scoreboard.php');
            echo '</div>';
            
          echo '</div>';
        }
        else printf('<div class="game"><div class="details__notes">%s</div></div>', $game['notes']);
      }
      
      if ( is_null($match['recorded']) ) {
        $action = 'submit';
        $submit = 'Submit to MMR';
      }
      
      if ( is_null($status) || $status == 'process' ) {
        printf('
          <div class="game__submit">
            <button class="btn btn--small" data-action="submitMatch" data-endpoint="stats/match" data-callback="reload" data-data=\'%s\'>%s</button>
          </div>',
          json_encode(array('action' => $action, 'matchID' => $matchID)), $submit
        );
      }
    ?></div>
</div>