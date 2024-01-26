<?php
  $countdown = false;
  $date      = false;
  $numbers   = false;
  $pieces    = array( 'd' => 'days', 'h' => 'hours', 'i' => 'minutes', 's' => 'seconds' );
  $tag       = get_field('flip_fonts') ? array('p', 'h6') : array('h6', 'p');
  $offset    = get_option('gmt_offset'); if ( $offset == 0 ) $offset = "+0";
  $tz        = new DateTimeZone($offset);
  $now       = new DateTime("now", $tz);
  
  if ( $time = get_field('teamup') ) {
    $pxl->teamup = new teamup();
    $upcoming  = $pxl->teamup->events_upcoming();
    $datetimes = array_column($upcoming, 'start_dt');
    
    foreach ($datetimes as $dt) {
      if ( strlen($dt) != 25 ) $dt .= '-04:00';
      $datetime = DateTime::createFromFormat("Y-m-d\TH:i:sT", $dt);
      
      $datetime->setTimeZone($tz);
      
      if ( $datetime >= $now ) {
        $time = $datetime->format('F d, Y g:i a');
        $date = $datetime;
        break;
      }
    }
  }
  else if ( $time = get_field('time') ) {
    $datetime = DateTime::createFromFormat('F j, Y g:i a P', "$time $offset");
    
    if ( $datetime > $now ) $date = $datetime;
  }
  
  if ( $date ) :
    $numbers = $date->diff($now);
?>
<div id="<?php echo $param['id']; ?>" class="<?php echo $param['class'] ?>">
  <?php pxlACF::field(array('tag' => 'h2'), 'title') ?>
  <div class="countdown" data-date="<?php echo $time ?>" data-offset="<?php echo $offset; ?>">
    <?php
      foreach($pieces as $piece => $title) {
        printf('
          <div>
            <%1$s class="number %2$s">%4$s</%1$s>
            <%3$s>%2$s</%3$s>
          </div>',
          $tag[0], $title, $tag[1], ( $numbers ? $numbers->$piece : 0 )
        );
      }
    ?>
  </div>
  
  <script>
    try {
      wp.domReady(function() {
        var <?php echo str_replace('-', '_', $param['id']) ?> = new pxlCountdown('#<?php echo $param['id'] ?>');
      });
    }
    catch (e) {}
    finally {
      document.addEventListener('DOMContentLoaded', function() {
        var <?php echo str_replace('-', '_', $param['id']) ?> = new pxlCountdown('#<?php echo $param['id'] ?>');
      });
    }
  </script>
</div>
<?php endif; ?>