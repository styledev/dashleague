<?php /* Template Name: Stats Manage */
  wp_enqueue_script('api');
  wp_enqueue_script('imask.min');
  wp_enqueue_script('api-form-stats');
  
  $maps = get_posts(array(
    'post_type'      => 'map',
    'posts_per_page' => -1,
    'order'          => 'ASC',
    'orderby' => array( 'meta_value' => 'ASC', 'title' => 'ASC' ),
    'meta_key'       => 'mode',
  ));
  
  $map_select = '';
  foreach ($maps as $post) {
    $mode = get_field('mode', $post->ID);
    if ( $mode === 'Deathmatch' ) continue;
    $map_select .= sprintf('<option value="%s" data-custom-properties=\'{"mode": "%s"}\'>[ %s ] %s</option>', $post->ID, $mode, strtolower($mode), $post->post_title);
  }
  
  $teams = array_column(get_posts(array(
    'post_type'      => 'team',
    'posts_per_page' => -1,
    'order'          => 'ASC',
    'orderby'        => 'title',
    'season'         => 'current',
  )), 'post_title', 'ID');
  
  $players = array();
  $team_select = '';
  
  global $wpdb;
  $rank_base = 1000;
  $players = array_column($wpdb->get_results("SELECT player_id, SUM(rank_gain) as `rank` FROM dl_players GROUP BY player_id"), 'rank', 'player_id');
  
  foreach ($teams as $post_id => $name) {
    $team_select .= sprintf('<option value="%s">%s</option>', $post_id, $name);
    
    if ( $team_players = get_field('players', $post_id) ) {
      foreach ($team_players as $player) {
        if ( isset($players[$player->ID]) && is_array($players[$player->ID]) ) continue;
        
        $rank = isset($players[$player->ID]) ? ($rank_base + $players[$player->ID]) : $rank_base;
        
        $players[$player->ID] = array(
          'name' => $player->post_title,
          'rank' => $rank ?: 1000,
          'team' => $name,
        );
      }
    }
  }
  
  $fields = array('player', 'kills', 'deaths', 'score');
  $per_team = 5;
?>
<style>
  form{margin:2em 0;}
  .teams{display:flex;justify-content:space-between;margin-top:2em;position:relative;}
  .teams:before{content:"VS";color:#cfcfcf;left:calc(50% - 50px);position:absolute;text-align:center;top:50%;width:100px;}
  .teams > .team,.fields{background-color:#fff;border-radius:4px;border:1px solid #cfcfcf;box-shadow:0 0 6px rgba(0,0,0,0.1);box-sizing:border-box;flex:0 0 48%;padding:1em;}
  .fields{margin-top:2em;}
  .fields__group{flex-wrap:wrap;}
  .fields__group, .fields__field{align-items:center;display:flex;justify-content:space-between;}
  .fields__group + .fields__group{margin-top:1em;}
  .fields__group .fields__field{flex:1 0 24%;}
  
  .fields__field{box-sizing:border-box;flex:1 0 auto;flex-direction:column;}
  .fields__field + .fields__field{margin-left:.25em;}
  .fields__field label{text-align:center;}
  .fields__field:not(.fields__field--split) > *{width:100%;}
  
  .fields__field .fields__field--sub{display:flex;justify-content:space-between;}
  .fields__field .fields__field--sub input{flex:1 1 48%;width:48%;}
  .fields__field .fields__field--sub input.time + input.time{margin-left:0.25em;}
  .fields__field .fields__field--sub input[type="text"]:not(.hide) + input:not(.time){display:none;}
  .fields__field--split{flex-direction:row;flex-wrap:wrap;}
  .fields__field--split label{flex:1 0 100%;}
  .fields__field--split > *{flex:1 0 32%;min-width:100px;}
  .fields__field--split > * + *{margin-left:.25em;}
  
  .fields__field--largechoices .choices__list--dropdown{width:110%;}
  .fields__field--widechoices .choices__list--dropdown{width:175%;}
  .fields__group--thirds .fields__field{flex:0 0 30%;}
  .fields__group--halves .fields__field{flex:1 0 48%;}
  
  .buttons{margin-top:2em;text-align:center;}
  button{background-color:#555;border-radius:4px;color:#fff;cursor:pointer;padding:.25em 2em;}
  button:hover{background-color:#000;}
  
  .choices__heading{text-align:center;}
  .choices__inner, .input{text-align:center;}
  .choices[data-type*="select-one"] select.choices__input{bottom:0;display:block !important;left:0;opacity:0;pointer-events:none;position:absolute;}
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<div class="content">
  <div class="alignwide">
    <h2>Enter Match Outcome</h2>
    <form class="lp-form" method="POST" data-init="stats" data-endpoint="stats/match" data-callback="stats_finish">
      <div class="fields">
        <div class="fields__group fields__group--thirds">
          <div class="fields__field fields__field--split fields__field--widechoices">
            <label class="label" for="info_date_month">Date</label>
            <select class="choices" name="info[date][month]" id="info_date_month" data-searchEnabled="false">
              <?php
                $date = explode('-', current_time('F-d-Y'));
                
                for( $m = 1; $m <= 12; ++$m ) {
                  $month_label = date('F', mktime(0, 0, 0, $m, 1));
                  $selected = $month_label === $date[0] ? ' selected' : '';
                  printf('<option value="%1$s"%2$s>%1$s</option>', $month_label, $selected);
                }
              ?>
            </select>
            <select class="choices" name="info[date][day]" id="info_date_year" data-searchEnabled="false">
              <?php 
                $start_date = 1;
                $end_date   = 31;
                for( $j = $start_date; $j <= $end_date; $j++ ) {
                  $selected = $j == $date[1] ? ' selected' : '';
                  printf('<option value="%1$s"%2$s>%1$s</option>', $j, $selected);
                }
              ?>
            </select>
            <select class="choices" name="info[date][year]" id="info_date_year" data-searchEnabled="false">
              <?php 
                $year = date('Y');
                $min  = $year - 1;
                $max  = $year;
                for( $i = $max; $i >= $min; $i-- ) {
                  $selected = $i == $date[2] ? ' selected' : '';
                  printf('<option value="%1$s"%2$s>%1$s</option>', $i, $selected);
                }
              ?>
            </select>
          </div>
          <div class="fields__field">
            <label class="label" for="map_id">Map</label>
            <select class="map choices" id="map_id" name="info[map_id]" required>
              <option value="">Choose</option>
              <?php echo $map_select; ?>
            </select>
          </div>
          <div class="fields__field fields__field--split">
            <label class="label" for="info_time_minutes">Total Map Play Time</label>
            <input class="input" type="text" name="info[time]" value="" id="info_time_minutes" mask="00:00" required>
          </div>
        </div>
      </div>
      
      <div class="teams">
        <?php for ($team_id = 0; $team_id < 2; $team_id++) include(PARTIAL . 'stats-form.php'); ?>
      </div>
      <div class="buttons">
        <button type="button" class="add">Add Map Data</button>
      </div>
      
      <ol class="match-data"></ol>
      
      <div class="buttons">
        <button type="button" class="btn submit">Submit Match Data</button>
        <button type="submit" class="btn errors hide">Errors</button>
      </div>
    </form>
  </div>
</div>

<script>
  var dl;
  
  window.addEventListener('DOMContentLoaded', (event) => {
    var players = Object.entries(JSON.parse('<?php echo addslashes(json_encode($players)); ?>')),
        masks   = document.querySelectorAll('input[mask]');
        
    dl = new apiFormStats(players);
    
    masks.forEach((masking) => {
      IMask(
        masking,
        {
          mask: 'mm:ss',
          lazy: false,
          overwrite: true,
          blocks: {
            mm: { mask: IMask.MaskedRange, placeholderChar: 'm', from: 0, to: 59, maxLength: 2 },
            ss: { mask: IMask.MaskedRange, placeholderChar: 's', from: 0, to: 59, maxLength: 2 }
          }
        }
      );
    });
  });
</script>
