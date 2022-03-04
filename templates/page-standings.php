<?php /* Template Name: Standings */
  global $wpdb;
  
  $global          = $pxl->stats->global();
  $standings       = $pxl->stats->data_standings();
  $top_ten_players = $pxl->stats->stats_top(10);
  
  $teams = array_column(get_posts(array(
    'post_type'      => 'team',
    'posts_per_page' => -1,
    'order'          => 'ASC',
    'orderby'        => 'title',
    'season'         => 'current',
  )), 'post_title', 'ID');
  
  $stats_cols = array(
    'Control Point'        => 8,
    'Domination'           => 8,
    'Interactions'         => 15,
    'Interactions/min'     => 16,
    'K/D'                  => 8,
    'Kills'                => 9,
    'Kills/min'            => 10,
    'Payload'              => 8,
    'Score'                => 12,
    'Score/min'            => 13,
    'Strength of Opponent' => 3,
    'Win Percentage'       => 7,
  );
  
  // $top_ten_teams   = $pxl->stats->top_ten_teams();
  // fns::put($top_ten_teams);
?>

<script src="<?php echo RES ?>/js/html2canvas.min.js"></script>

<div class="content">
  <div class="bar wp-block-group alignfull">
    <div class="bar__container wp-block-group__inner-container">
      <div class="bar__wrapper alignwide">
        <div class="bar__pos bar__pos--left">
          <div class="bar__info">
            <div class="bar__title">Standings</div>
            <div class="bar__rp">
              <div class="bar__score">&</div>
              <div class="bar__tag bar__tag--expand">Top Stats</div>
            </div>
          </div>
        </div>
        <div class="bar__pos bar__pos--right">
          <div class="bar__pill bar__pill--time" title="total time played">
            <span class="bar__icon"><i class="fas fa-clock"></i></span>
            <?php echo $global['time']; ?>
          </div>
          <div class="bar__pill bar__pill--players" title="players">
            <span class="bar__icon"><i class="fas fa-head-vr"></i></span>
            <?php echo $global['players']; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="alignwide">
    <div class="grid">
      <div class="standings grid__item grid__item--fourth">
        <div class="stats">
          <div class="stat">
            <h6 class="stat__heading">
              <span>Standings</span>
              <span>MMR</span>
            </h6>
            <?php
              foreach ($standings as $key => $team) {
                $pos = $key + 1;
                $slug = str_replace('-', '', $team->name);
                
                printf(
                  '
                    <div class="stat__list" data-team="%s">
                      <div class="stat_player" data-key="%2s">
                        <span class="stat_player__pos">%2$s.</span>
                        <a href="/teams/%s">
                          <span class="stat_player__name">%1$s</span>
                          <span class="stat_player__stat">%s</span>
                        </a>
                      </div>
                    </div>
                  ',
                  $team->name, $pos, $slug, $team->mmr
                );
              }
            ?>
          </div>
        </div>
      </div>
      <div class="grid__item grid__item--threefourth">
        <div class="stats">
          <?php
            foreach ($top_ten_players as $area => $players) {
              $area = explode('|', $area);
              if ( !isset($area[1]) ) $area[1] = '';
              
              $col = isset($stats_cols[$area[0]]) ? $stats_cols[$area[0]] : 1;
              
              echo '<div class="stat">';
                printf('<h6><a href="/standings/stats/?order=[[%s,%%22desc%%22]]" class="stat__heading"><span>%s</span><span>%s</span></a></h6>', $col, $area[0], $area[1]);
                echo '<div class="stat__list">';
                  foreach ($players as $key => $player) {
                    $pos  = $key + 1;
                    
                    switch ($area[0]) {
                      case 'Win Percentage':
                        $stat = "{$player->stat}%";
                      break;
                      case 'Domination':
                      case 'Payload':
                      case 'Control Point':
                        $stat = $player->stat;
                      break;
                      default:
                        $stat = number_format($player->stat, $player->stat > 100 ? 0 : 2);
                        break;
                    }
                    
                    printf(
                      '<div class="stat_player" data-key="%s">
                        <span class="stat_player__pos">%1$s.</span>
                        <a href="/players/%s">
                          <span class="stat_player__name">%s</span>
                          <span class="stat_player__team" data-team="%4$s">%s</span>
                          <span class="stat_player__stat">%s</span>
                        </a>
                      </div>',
                      $pos, $player->slug, $player->name, $teams[$player->team_id], $stat
                    );
                  }
                echo '</div>';
              echo '</div>';
            }
          ?>
        </div>
        <div class="links">
          <a href="/standings/stats/" class="btn btn--ghost btn--small">All Player Stats</a>
        </div>
      </div>
    </div>
  </div>
  <script>
    var standings = document.querySelector('.standings .stats'), navbar = document.querySelector('.navbar');
    
    navbar.classList.add('navbar--fixed');
    navbar.classList.remove('navbar--smart');
    
    standings.addEventListener('mouseover', function(e) {
      var $ = e.target.closest('[data-team]')
          t = document.querySelectorAll('.stat_player__team--highlight');
          
      t.forEach(function(team) {
        team.classList.remove('stat_player__team--highlight');
      });
      
      if ( $ && $.dataset.team ) {
        var h = document.querySelectorAll('.stat_player__team[data-team="'+$.dataset.team+'"]');
        
        h.forEach(function(team) {
          team.classList.add('stat_player__team--highlight');
        })
      }
    });
  </script>
</div>