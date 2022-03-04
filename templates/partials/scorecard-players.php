<div class="players">
  <?php
    foreach ($players as $key => $player) {
      $pos = $key+1;
      
      printf('
        <div class="player">
          <span class="player__identity">
            <span class="player__identity--position">%s. </span>
            <span class="player__identity--team">%s</span>
            <span class="player__identity--name">%s</span>
          </span>
          <span class="player__stat">%s</span>
          <span class="player__stat">%s</span>
          <span class="player__stat">%s</span>
        </div>',
        $pos, $player['team'], $player['name'], $player['kills'], $player['deaths'], $player['score']
      );
    }
  ?>
</div>