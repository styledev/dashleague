<?php
  // New Attempt
  wp_enqueue_script('api');
  
  $onException = function ($exception) {
  	echo "Exception: ", $exception->getMessage(), "\n";
  };

  // set_exception_handler($onException);
  
  class matches_competitors{
    public $cycles, $matches, $query = [];
    public $competitors = [],
           $errors      = [],
           $gamer_ids   = ['full' => [], 'partial' => []],
           $players     = ['handles' => [], 'gamer_ids'],
           $teams       = [];
           
    function __construct() {
      global $pxl;
      
      $this->list_cycles();
      
      $this->matches = $pxl->stats->games($this->query);
      
      $this->list_players();
      
      $this->list_teams();
      
      $this->verify_match_players();
    }
    
    private function find_player( $match_id, $stat ) {
      $gamer_id = explode('-', $stat->id);
      $handle   = strtolower($stat->name);
      $player   = FALSE;
      $pid      = FALSE;
      
      // Find by Gamer ID
        if ( isset($this->gamer_ids['full'][$stat->id]) ) {
          $pid = $this->gamer_ids['full'][$stat->id];
          $player    = $this->players['pids'][$pid];
        }
        
      // Find by Name
        if ( !$player && isset($this->players['handles'][$handle]) ) {
          $player = $this->players['handles'][$handle];
          $pid    = $player['pid'];
          
          $player['errors'][$pid] = "{$handle} does not match handle on file: {$player['handle']}";
        }
        
      // Find by Partial Gamer ID
        if ( !$player && isset($this->gamer_ids['partial'][$gamer_id[0]]) ) {
          $pid    = $this->gamer_ids['partial'][$gamer_id[0]];
          $player = $this->players['pids'][$pid];
          
          $player['errors'][$pid] = "{$stat->id} not listed: " . implode(', ', $player['gamer_ids']);
        }
        
      // Add Match
        if ( $player ) {
          if ( !isset($this->competitors[$pid]) ) $this->competitors[$pid] = $player;
          if ( !isset($this->competitors[$pid]['matches']) ) $this->competitors[$pid]['matches'] = [];
          if ( !isset($this->competitors[$pid]['matches'][$match_id])) $this->competitors[$pid]['matches'][$match_id] = [];
          
          $this->competitors[$pid]['matches'][$match_id][] = $stat;
          
          if ( $team = $this->teams[strtolower($stat->tag)] ) {
            if ( !in_array($pid, $team) ) {
              $player['errors'][$pid] = "{$player['handle']} not rostered on team: {$stat->tag}";
            }
          }
          
        }
        else $this->errors[$stat->id] = $stat;
        
      return $pid;
    }
    
    private function gamer_id( $field, $player_id ) {
      if ( $id = get_field($field, $player_id) ) {
        if ( strpos($id, '-') !== FALSE ) {
          $partial = explode('-', $id);
          
          $this->gamer_ids['full'][$id]            = $player_id;
          $this->gamer_ids['partial'][$partial[0]] = $player_id;
          
          return $id;
        }
      }
      
      return FALSE;
    }
    
    private function list_cycles() {
      global $pxl;
      
      $this->cycles = $pxl->stats->cycles();
      
      if ( !empty($this->cycles) ) {
        $cycle = array_shift($this->cycles);
        $this->query['where'] = sprintf("gs.datetime >= '%s'", $cycle['start']);
      }
    }
    private function list_players() {
      $players = get_posts([
        'post_type'      => 'player',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'season'         => 'current',
      ]);
      
      foreach ($players as $player) {
        $data = [
          'pid'       => $player->ID,
          'handle'    => $player->post_title,
          'discord'   => get_field('discord_username', $player->ID),
          'gamer_ids' => array_filter([
            $this->gamer_id('gamer_id', $player->ID),
            $this->gamer_id('gamer_id_alt', $player->ID),
          ])
        ];
        
        $this->players['handles'][strtolower($player->post_title)] = $data;
        $this->players['pids'][$player->ID]                        = $data;
      }
    }
    private function list_teams() {
      $teams = array_column(get_posts([
        'order'          => 'ASC',
        'orderby'        => 'post_title',
        'posts_per_page' => -1,
        'post_type'      => 'team',
        'season'         => 'current',
      ]), 'ID', 'post_title');
      
      foreach ($teams as $team => $team_id) {
        $this->teams[strtolower($team)] = array_column(get_field('players', $team_id), 'ID');
      }
    }
    
    public function verify_match_players() {
      foreach ($this->matches as $match_id => $match) {
        $games = array_reverse($match['games']);
        
        foreach ($games as $game) {
          $colors = array_column($game['teams'], 'players', 'color');
          
          if ( is_array($colors['red']) && is_array($colors['blue']) ) {
            $stats = array_merge($colors['red'], $colors['blue']);
            
            foreach ($stats as $stat) {
              if ( !isset($this->competitors[$stat->name]) ) {
                $pid = $this->find_player($match_id, $stat);
                
                if ( $pid ) {
                  // fns::put($this->competitors[$pid]);
                }
                
                // if ( $player ) {
                //   if ( !isset($this->competitors[$player_id]) ) $this->competitors[$player_id] = $player;
                //
                //   $this->competitor($this->competitors[$player_id], $match_id, $stat);
                //
                //   fns::log($this->competitors[$player_id]);
                // }
                
                // $team = isset($teams[$player->tag]) ? $teams[$player->tag] : FALSE;
                
                // $player_ids[$player->name] = array(
                //   'tag'        => $player->tag,
                //   'name'       => $player->name,
                //   'gamer_id'   => $player->id,
                //   'gamer_ids'  => [],
                //   'record' => [
                //     'id'        => $player_id,
                //     'name'      => $competitor,
                //     'matched'   => strtolower($competitor) == strtolower($player->name),
                //     'gamer_ids' => $player_id ? array_unique(array_filter(array(get_field('gamer_id', $player_id), get_field('gamer_id_alt', $player_id)))) : [],
                //     'team'      => $team && in_array($player_id, $team) ? $player->tag : FALSE,
                //     'team_id'   => $team && in_array($player_id, $team) ? $_teams[$player->tag] : FALSE,
                //     'rostered'  => $team && in_array($player_id, $team),
                //   ]
                // );
                
                // sort($player_ids[$player->name]['record']['gamer_ids']);
              }
              
              // $games = array_values($match['games']);
              // $player_ids[$player->name]['gamer_ids'][] = $player->id;
              // $player_ids[$player->name]['gamer_ids']   = array_unique($player_ids[$player->name]['gamer_ids']);
              //
              // sort($player_ids[$player->name]['gamer_ids']);
              //
              // $player_ids[$player->name]['matches'][]   = $games[0]['matchID'];
              // $player_ids[$player->name]['matches']     = array_unique($player_ids[$player->name]['matches']);
            }
          }
        };
        
        // fns::put($match);die;
      }
      
      fns::log("errros");
      fns::log($this->errors);
    }
  }
  
  $data = new matches_competitors();
?>
<style>
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;text-align:left;}
  tbody tr:nth-child(odd){background:#eee;}
  .nofill{pointer-events:none;}
</style>

<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="list alignwide">
      <table>
        <thead>
          <tr>
            <th>Tag</th>
            <th>Name</th>
            <th>Status</th>
            <th>#</th>
            <th>IDs</th>
          </tr>
        </thead>
        <tbody>
          <?php
            
          ?>
        </tbody>
      </table>
    </div>
    <div class="tml alignwide">
      <br>
      <form class="tml inline" method="POST" data-init="init" data-endpoint="stats/gamer" data-callback="reload" data-confirm="true" novalidate="novalidate" accept-charset="utf-8">
        <div class="tml-alerts"><ul class="tml-messages"></ul></div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="tag">Team</label>
          <input class="tml-field nofill" type="text" name="tag" value="" id="tag" required>
        </div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="name_current">Name</label>
          <input class="tml-field nofill" type="text" name="name_current" value="" id="name_current" required>
        </div>
        <div class="tml-field-wrap">
          <label class="tml-label" for="name_corrected">Correction</label>
          <input class="tml-field" type="text" name="name_corrected" value="" id="name_corrected" required>
        </div>
        <div class="tml-field-wrap tml-submit-wrap">
          <button name="submit" type="submit" class="tml-button btn--small">
            Change Name
          </button>
        </div>
      </form>
    </div>
  </div>
  <script>
    var gi;
    
    document.addEventListener("DOMContentLoaded", function(event) {
      gi = new dlAPI;
      
      var list           = document.querySelector('.list'),
          tag            = document.querySelector('#tag'),
          name_current   = document.querySelector('#name_current'),
          name_corrected = document.querySelector('#name_corrected');
          
      list.addEventListener('click', function(e) {
        if ( e.target.tagName === 'BUTTON' ) {
          var data = JSON.parse(e.target.dataset.data);
          tag.value = data.tag;
          name_current.value = data.name;
          gi.el.$form.scrollIntoView();
          name_corrected.focus();
        }
      })
    });
  </script>
</div>













