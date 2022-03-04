<?php
  $team = $player->team;
  $max = 12;
  
  if ( $player->active ) :
?>
    <style>
      .acf-relationship .list{height:460px!important;}
      .acf-form-submit{padding:12px;}
      .acf-form-submit input[type="submit"]{width:100%;}
      div[data-name="looking_for_players"]{display:flex;align-items:center;justify-content:center;}
      div[data-name="looking_for_players"] .acf-label{margin:0 1em 0 0;}
      div[data-name="looking_for_players"] .acf-label label{margin:0;}
    </style>
    <div class="tml">
      <div class="team-card">
        <div class="team-card__avatar"><?php printf('<img src="%s" height"100" width="100"/>', $team->logo) ?></div>
        <div class="team-card__title">
          <?php echo $team->name; ?>
        </div>
      </div>
      <?php
        if ( $team && $player->captain ) :
          echo '<br><div class="notice notice--red center-text"><small>Players shown on the right side below but not on the left have not signed up on the site yet. If you remove them you won\'t be able to re-add them until they do.</small></div>';
          acf_form(array(
            'post_id'           => $team->id,
            'post_title'        => FALSE,
            'post_content'      => FALSE,
            'fields'            => array(
              'field_61d1015c63267', /* Looking for Players */
              'field_5fb1e370c7545', /* Players */
            ),
          ));
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