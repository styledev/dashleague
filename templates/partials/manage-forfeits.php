<?php
  global $wpdb;
  
  wp_enqueue_script('api');
  wp_enqueue_script('api-manage');
  
  $teams = array_column(get_posts(array(
    'post_type'      => 'team',
    'posts_per_page' => -1,
    'order'          => 'ASC',
    'orderby'        => 'title',
    'season'         => 'current',
  )), 'post_title', 'ID');
  
  $matchIDs               = $wpdb->get_col("SELECT matchID, count(matchID) as maps FROM dl_game_stats WHERE recorded IS NULL GROUP BY matchID HAVING maps < 2");
  $adjustments_game_stats = $wpdb->get_results("SELECT * FROM dl_game_stats WHERE game_id IN ('forfeit', 'double-forfeit', 'map-forfeit') ORDER BY datetime DESC");
?>
<style>
  select{width:100%;}
  .tml.inline .tml-field-wrap{flex: 0 0 19%;}
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;text-align:left;}
  tbody tr:nth-child(odd){background:#eee;}
</style>

<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="tml alignwide">
      <form class="tml inline" method="POST" data-init="init" data-endpoint="stats/forfeit" data-callback="reload" data-confirm="true" novalidate="novalidate" accept-charset="utf-8">
        <div class="tml-alerts"><ul class="tml-messages"></ul></div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="forfeit_type">Forfeit Type (Match/Map)</label>
          <select name="forfeit_type" id="forfeit_type">
            <option value="match">Match</option>
            <option value="double-forfeit">Double Forfeit</option>
            <?php foreach ($matchIDs as $matchID) printf('<option value="%1$s">%1$s</option>', $matchID); ?>
          </select>
        </div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="team_forfeit">Forfeiting Team</label>
          <select class="teams" id="team_forfeit" name="team_forfeit" required>
            <option value="">Choose Team</option>
            <?php foreach ($teams as $team_id => $team_name) printf('<option value="%s">%s</option>', $team_id, $team_name); ?>
          </select>
        </div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="team_opponent">vs Team</label>
          <select class="teams" id="team_opponent" name="team_opponent" required>
            <option value="">Choose Team</option>
            <?php foreach ($teams as $team_id => $team_name) printf('<option value="%s">%s</option>', $team_id, $team_name); ?>
          </select>
        </div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="forfeit_date">Date</label>
          <input class="tml-field" type="text" name="forfeit_date" value="" id="forfeit_date" placeholder="YYYYMMDD" pattern="[0-9]{4}[0-9]{2}[0-9]{2}" required>
        </div>
        <div class="tml-field-wrap tml-submit-wrap">
          <button name="submit" type="submit" class="tml-button btn--small">
            Process Forfeit
          </button>
        </div>
      </form>
    </div>
    <div class="list alignwide">
      <br/>
      <h5>DL Game Stats</h5>
      <table>
        <thead>
          <th width="220px">MatchID</th>
          <th width="200px">Datetime</th>
          <th>Teams</th>
        </thead>
        <tbody>
          <?php
            foreach($adjustments_game_stats as $game) {
              printf(
                '<tr><th>%s</th><td>%s</td><td>%s</td></tr>',
                $game->matchID, $game->datetime, $game->notes
              );
            }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <script>
    var el = {
      'forfeit_type': document.querySelector('#forfeit_type'),
      'forfeit_date': document.querySelector('#forfeit_date'),
      'team_forfeit': document.querySelector('#team_forfeit'),
      'team_opponent': document.querySelector('#team_opponent')
    };
    
    var options = [...el.team_forfeit.children, ...el.team_opponent.children], teams = false;
    
    el.forfeit_type.addEventListener('change', function(e) {
      var value = e.target.selectedOptions[0].value;
      
      jQuery('.teams option').prop('disabled', false).prop('hidden', false);
      
      if ( value == 'match' || value == 'double-forfeit' ) {
        el.forfeit_date.value = '';
        el.forfeit_date.readOnly = false;
        el.team_forfeit.value = '';
        el.team_opponent.value = '';
      }
      else {
        var parts = value.split('=');
        
        if ( parts.lenght == 2) teams = parts[1].split('<>');
        
        el.forfeit_date.value = parts[0];
        el.forfeit_date.readOnly = true;
        
        if ( teams ) {
          options.forEach(function(opt) {
            if ( !teams.includes(opt.innerText) ) {
              opt.disabled = true;
              opt.hidden   = true;
            }
          });
        }
      }
    });
    
    el.team_forfeit.addEventListener('change', function(e) {
      var value = e.target.selectedOptions[0].value, options = [...el.team_opponent.children];
      
      el.team_opponent.value = '';
      
      options.forEach(function(opt) {
        if ( opt.value == value ) {
          opt.disabled = true;
          opt.hidden   = true;
        }
        else if ( teams && teams.includes(opt.innerText) ) {
          opt.disabled = false;
          opt.hidden   = false;
        }
      });
    });
  </script>
</div>