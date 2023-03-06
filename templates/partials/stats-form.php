<div class="team">
  <div class="fields__group">
    <div class="fields__field">
      <label class="label" for="team_<?php echo $team_id; ?>">Team</label>
      <select class="team" id="team_<?php echo $team_id; ?>" name="teams[<?php echo $team_id; ?>][team_id]" required>
        <option value="">Choose</option>
        <?php echo $team_select; ?>
      </select>
    </div>
  </div>
  <div class="fields__group fields__group--halves">
    <div class="fields__field">
      <label class="label" for="team_<?php echo $team_id; ?>_outcome">Outcome<br><small>(Won or Lost)</small></label>
      <select class="outcome" name="teams[<?php echo $team_id; ?>][outcome]" id="team_<?php echo $team_id; ?>_outcome" data-searchEnabled="false" required>
        <option value="">Choose</option>
        <option value="0">Lost</option>
        <option value="1">Won</option>
      </select>
    </div>
    <div class="fields__field">
      <label class="label" for="team_<?php echo $team_id; ?>_score_points">Score<br><small>(Points OR % and Time)</small></label>
      <div class="fields__field--sub">
        <input class="score time input hide" type="number" name="teams[<?php echo $team_id; ?>][score_percentage]" value="" id="team_<?php echo $team_id; ?>_score_percentage" placeholder="%">
        <input class="score time input hide" type="text" name="teams[<?php echo $team_id; ?>][score_time]" value="" id="team_<?php echo $team_id; ?>_score_time" mask="00:00">
        <input class="score points input" type="number" name="teams[<?php echo $team_id; ?>][score_points]" min="0" value="" id="team_<?php echo $team_id; ?>_score_points" placeholder="#" required>
      </div>
    </div>
  </div>
  <?php
    for ($i=1; $i <= $per_team; $i++) {
      echo '<div class="fields__group">';
        foreach ($fields as $field) {
          $class = "input";
          $title = ucwords($field);
          if ( $field === 'player' ) {
            $title .= " #{$i}";
            $field = 'player_id';
          }
          
          printf('
            <div class="fields__field ">
              <label class="label" for="team_player_%1$d_%2$d_%2$s">%4$s</label>
          ', $team_id, $i, $field, $title);
          
          if ( $field === 'player_id' ) {
            printf('
              <select class="input player" name="teams[%1$d][players][%2$d][%3$s]" value="" id="team_player_%1$d_%2$d_%2$s" data-team="team_%1$d" required>
                <option value="">Choose</option>
              </select>
              ', $team_id, $i, $field, $title
            );
          }
          else {
            printf('<input class="input" type="number" name="teams[%1$d][players][%2$d][%3$s]" value="" id="team_player_%1$d_%2$d_%2$s" required>', $team_id, $i, $field, $title);
          }
          
          echo '</div>';
        }
      echo '</div>';
    }
  ?>
</div>