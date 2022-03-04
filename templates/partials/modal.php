<div id="modals">
<?php
  foreach ($this->templates[$area] as $index => $post) {
    $modal = get_field('modal');
    
    $data  = array(
      'trigger' => $modal['trigger'],
      'id'      => $modal['id'],
      'snooze'  => $modal['trigger'] === 'click' ? 0 : $modal['snooze'],
    );
    
    if ( $data['trigger'] === 'scroll' && isset($modal['distance']) ) {
      $data['desktop'] = $modal['distance']['desktop'];
      $data['d-measurement'] = $modal['distance']['desktop_measurement'];
      $data['mobile'] = $modal['distance']['mobile'];
      $data['m-measurement'] = $modal['distance']['mobile_measurement'];
    }
    
    if ( $data['trigger'] === 'time' && isset($modal['timing']) ) {
      $data['desktop'] = $modal['timing']['desktop'] * 1000;
      $data['mobile']  = $modal['timing']['mobile'] * 1000;
    }
    
    $data = fn::array_to_attrs($data, 'data');
    
    setup_postdata($post);
    printf('
      <div id="modal-%s" class="modal mfp-hide"%s>
            <a href="#" class="closebutton"><i class="far fa-times"></i></a>
            <div class="content">', $modal['id'], " $data");
              the_content();
    echo '  </div>
      </div>
    ';
  }
?>
</div>
<script>
  window.addEventListener('DOMContentLoaded', (event) => {
    var $modals = document.querySelectorAll('.modal');
    
    if ( $modals ) {
      $modals.forEach(function(modal) {
        modal.modal = new primeModal(modal);
      });
    }
  });
</script>