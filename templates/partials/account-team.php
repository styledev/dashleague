<?php
  global $pxl, $team;
  $team = $user->team;
  
  $max = 12;
  
  add_action('acf/render_field', function($field) {
    if ( $field['label'] == 'Ideal Servers' ) {
      global $team;
      $team->timezones();
      $team->servers();
      
      $team->table('timezones');
      $team->table('servers');
      
      printf("<label><strong>Override Ideal Servers (max 2)</strong>%s</label>", (
        $field['disabled'] ? ' <small style="color:red">Reach out to the DL Board to Change</small>' : ''
      ));
    }
  }, 1);
  
  add_action('acf/render_field', function($field) {
    if ( $field['label'] == 'Ideal Servers' ) {
      foreach ($field['value'] as $value) {
        printf('<input type="hidden" name="acf[field_63b3549593b67][]" value="%s">', $value);
      }
    }
  }, 99);
  
  add_filter('acf/render_field', function($field) {
    if ( $field['label'] == 'Players' ) {
      global $team;
      
      $unregistered = array_column($team->roster['error'], 'name');
      
      echo '
        <br>
        <label><strong>Gamer IDs</strong></label>
        <div class="team">
        <table>
          <thead>
            <tr>
              <th>Player</th>
              <th>Gamer ID on File?</th>
            </tr>
          </thead>
          <tbody>
      ';
      
      foreach ($unregistered as $player) {
        printf(
          '<tr>
            <th align="left">%s</th>
            <td align="right">
              <span style="color:red"><small>Ineligible to play until they REGISTER</small></span>
            </td>
          </tr>',
          $player
        );
      }
      
      foreach ($team->roster['players'] as $player) {
        printf(
          '<tr>
            <th align="left">%s</th>
            <td align="right">
              %s
              %s
            </td>
          </tr>',
          $player['name'],
          ($player['gamer_id'] ? sprintf('<strong>%s</strong>', $player['gamer_id']) : '<span style="color:red"><small>Ineligible to play until they set their GAMER ID</small></span>'),
          ($player['gamer_id_alt'] ? sprintf('<br/><small style="color:blue"><strong>[ALT]</strong> %s</small>', $player['gamer_id_alt']) : '')
        );
      }
      
      echo "</tbody></table></div>";
    }
  }, 20);
  
  add_filter('acf/load_field', function( $field ) {
    global $pxl;
    
    if ( $field['label'] == 'Ideal Servers' ) {
      $field['disabled'] = $pxl->season_dates['locked_names'];
    }
    
    return $field;
  });
  
  if ( $user->active || $pxl->access('administrator')) :
?>
    <style>
      .acf-relationship .list{height:460px!important;}
      .acf-form-submit{padding:12px;}
      .acf-form-submit input[type="submit"]{width:100%;}
      div[data-name="looking_for_players"]{display:flex;align-items:center;justify-content:center;}
      div[data-name="looking_for_players"] .acf-label{margin:0 1em 0 0;}
      div[data-name="looking_for_players"] .acf-label label{margin:0;}
      
      .acf-field-61d1015c63267 .acf-input{min-width:80px;}
      .acf-field-6223b3298fdb7 button{width:100%;}
      .acf-field-6223b3298fdb7 .acf-label:after{
        content:'Build your roster from the registered players below. Once they are rostered you can drag them to set their order or click on the remove icon.';
      }
      .acf-field-6223b3298fdb7 .acf-input{display:none;}
      
      @media(max-width: 780px) {
        .acf-field-6223b3298fdb7 .acf-input{display:block;}
        .acf-field-6223b3298fdb7 .acf-input:before{
          content: "For mobile you might need to toggle the action you want to take with this button.";
          display:inline-block;
          margin-bottom:1em;
        }
      }
      
      .acf-field.acf-field-5fb1e370c7545 .acf-label label{display:none;}
      
      .acf-relationship .filters{display:none;}
      .choices:before{background-color:var(--blue-light);color:#fff;content:'REGISTERED';display:block;padding:0.25em 0;text-align:center;}
      .values:before{background-color:var(--green);color:#fff;content:'ROSTERED';display:block;padding:0.25em 0;text-align:center;}
      
      .team th, .team td{padding:.25em;}
    </style>
    <div class="tml">
      <div class="team-card">
        <div class="team-card__avatar"><?php printf('<img src="%s" height"100" width="100"/>', $team->logo) ?></div>
        <div class="team-card__title">
          <?php echo $team->name; ?>
        </div>
      </div>
      <?php
        // fns::error();
        
        if ( ($team && $user->captain && !$pxl->season_dates['locked']) || $pxl->access('administrator') ) :
          echo '<br><div class="notice notice--red center-text"><small>Players shown on the right side below but not on the left have not signed up on the site yet. If you remove them you won\'t be able to re-add them until they do.</small></div>';
          
          if ( !empty($team->roster['requesting']) ) {
            echo '<h3>Players Requesting to join your team</h3><ul>';
            foreach ($team->roster['requesting'] as $d => $p) {
              printf('
                <li>
                  <strong>%s</strong>
                  <small>%s</small>
                </li>
                ', $p, $d
              );
            }
            echo '</ul>';
          }
          
          acf_form(array(
            'post_id'           => $team->id,
            'post_title'        => FALSE,
            'post_content'      => FALSE,
            'fields'            => array(
              'field_61d1015c63267', /* Looking for Players */
              'field_6223b3298fdb7', /* Instructions */
              'field_5fb1e370c7545', /* Players */
              'field_63b3549593b67', /* Ideal Servers */
            )
          ));
          
          echo '
            <script type="text/javascript">
              function playersEdit() {
                var field = acf.getField("field_5fb1e370c7545"),
                    status = field.$el.data("status");
                    
                if ( status == "disabled" ) {
                  field.$list("values").sortable("enable");
                  field.$el.data("status", "enabled");
                  dl.el.$buttonSubmit.innerText = "Toggle Remove";
                }
                else {
                  field.$list("values").sortable("disable");
                  field.$el.data("status", "disabled");
                  dl.el.$buttonSubmit.innerText = "Toggle Sort";
                }
              }
              
              acf.add_filter("select2_args", function( args, $select, settings ){
                args.maximumSelectionLength = 2;
                return args;
              });
            </script>
          ';
        else:
      ?>
          <div>
            <h5>
              Roster
              <?php printf('(%d / %d)', $max - $team->roster['spots'], $max); ?>
            </h5>
            <ul style="list-style:none;columns:3">
              <?php
                foreach ($team->roster['players'] as $discord => $pl) {
                  $link = get_permalink($pl['ID']);
                  printf('<li><a href="%s">%s</a></li>', $link, $pl['name']);
                }
                
                foreach ($team->roster['error'] as $discord => $pl) {
                  printf("<li>%s<sup>*</sup></li>", $pl['name']);
                }
              ?>
            </ul>
            <small>* Player has not signed up on the site yet.</small>
          </div>
      <?php endif; ?>
    </div>
<?php elseif ( is_object($team) ): ?>
  <div class="tml">
    <div class="team-card">
      <div class="team-card__avatar"><?php printf('<img src="%s" height"100" width="100"/>', $team->logo) ?></div>
      <div class="team-card__title">
        <?php echo $team->name; ?>
      </div>
    </div>
    <br>
    <div class="notice notice--red center-text">You have asked to join <?php echo $team->name; ?>, once confirmed you will see your team details here.</div>
  </div>
<?php elseif ( 'Free Agent' == $team ): ?>
  <style>
    .team-card__title a{color:var(--hd-blue);}
    .team-card__title a:hover{color:var(--hd-red);}
  </style>
  <div class="tml">
    <h4>Status: Free Agent</h4>
    
    <h6>Take a look at the current teams that are openly recruiting!</h6>
    
    <div class="team-cards">
      <?php
        pxl::loop('team-recruiting', array(
          'post_type'    => 'team',
          'meta_key'     => 'looking_for_players',
          'meta_value'   => 1,
          'meta_compare' => '='
        ));
      ?>
    </div>
  </div>
<?php else: ?>
  <div class="tml">
    <h1>Your profile is set to inactive</h1>
  </div>
<?php endif; ?>