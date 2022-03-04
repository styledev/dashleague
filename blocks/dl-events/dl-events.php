<?php
  global $pxl;
  if ( !isset($pxl->teamup) ) $pxl->teamup = new teamup();
  $events = $pxl->teamup->matches_upcoming();
?>
<div class="events wp-block-group alignfull">
  <div class="events__wrapper wp-block-group__inner-container">
    <?php
      foreach ($events as $title => $matches) {
        $matches = $title == 'Past Matches' ? $matches : array_reverse($matches);
        $slug    = sanitize_title($title);
        
        if ( !empty($matches) ) {
          printf('<div class="events__container event__container--%s alignwide" data-title="%s">', $slug, $title);
            foreach ($matches as $match) echo $match;
          echo '</div>';
        }
      }
    ?>
  </div>
</div>