<?php
  $player = new dlPlayer();
  $team   = $player->team['captain'] ? new dlTeam($player->team['captain']) : FALSE;
?>
<style>
  
</style>
<div class="tml">
  <?php if ( $team ) : ?>
    <h4><?php echo $team->name; ?></h4>
    <h5>Captains: <small><?php echo implode(', ', $team->captains); ?></small></h5>
    <h6>Roster</h6>
    
    <ul>
      <?php
      fns::put($team->roster);die;
        foreach ($team->roster['players'] as $key => $player) {
          fns::put($player);die;
          echo "<li>{$player->post_title}</li>";
        }
      ?>
    </ul>
    
  <?php endif; ?>
</div>