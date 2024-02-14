<div id="<?php echo $block['id'] ?>" class="block_compensated alignwide">
  <?php
    global $post;
    $form_id = preg_match('/"formId":"([0-9]+)"/', $post->post_content, $forms);
    
    if ( isset($forms[1]) ) {
      $entries = GFAPI::get_entries($forms[1], ['status' => 'active'], ['key' => 5, 'direction' => 'ASC']);
      // fns::put($entries);
      // $entries = array_reverse($entries);
      usort($entries, fn($a, $b) => $a['5'] <=> $b['5']);
      
      if ( !empty($entries) ) {
        $table = '
          <table>
            <thead>
              <tr>
                <th class="team">Team</th>
                <th class="players">Player(s)</th>
                <th class="sponsor">Sponsor</th>
                <th class="comp">Compensation</th>
                <th class="req">Requirements</th>
                <th class="term">Term</th>
                <th class="date">Submitted</th>
              </tr>
            </thead>
            <tbody>%s</tbody>
          </table>
        ';
        
        $team  = FALSE;
        $items = [];
        
        foreach ($entries as $entry) {
          if ( $team && $team !== trim($entry[5]) ) {
            $team = trim($entry[5]);
            printf($table, implode("\r\n", $items));
            $items = [];
          }
          
          $date = DateTime::createFromFormat('Y-m-d H:i:s', $entry['date_created']);
          
          $items[] = sprintf('<tr>
            <th class="team">%s</th>
            <td class="players">%s</th>
            <td class="sponsor">%s</td>
            <td class="comp">%s</td>
            <td class="req">%s</td>
            <td class="term">%s</td>
            <td class="date">%s</td>',
            $entry[5],
            $entry[6],
            $entry[7],
            $entry[8],
            $entry[10],
            $entry[9],
            $date->format('Y-m-d')
          );
          
          $team = $entry[5];
        }
      }
    }
  ?>
</div>