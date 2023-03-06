<div id="<?php echo $block['id'] ?>" class="block_compensated">
  <?php
    global $post;
    $form_id = preg_match('/"formId":"([0-9]+)"/', $post->post_content, $forms);
    
    if ( isset($forms[1]) ) {
      $entries = GFAPI::get_entries($forms[1]);
      usort($entries, fn($a, $b) => $a['5'] <=> $b['5']);
      
      if ( !empty($entries) ) {
        echo '<table>';
        echo '
          <thead>
            <tr>
              <th>Team</th>
              <th>Player(s)</th>
              <th>Sponsor</th>
              <th>Compensation</th>
              <th>Term</th>
              <th>Requirements</th>
            </tr>
          </thead>
        ';
        
        foreach ($entries as $entry) {
          printf('<tr>
            <th>%s</th>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>',
            $entry[5],
            $entry[6],
            $entry[7],
            $entry[8],
            $entry[9],
            $entry[10],
          );
        }
        echo '</table>';
      }
    }
  ?>
</div>