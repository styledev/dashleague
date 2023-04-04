<?php if ( !class_exists('dlTeam') ) {
  class dlTeam {
    function __construct( $team = FALSE ) {
      if ( !$team ) {
        global $post;
        $team = $post;
      }
      
      $this->id    = $team->ID;
      $this->name  = $team->post_title;
      $this->link  = get_permalink($team->ID);
      $this->logo  = pxl::image($this->id, array('w' => 256, 'h' => 256, 'return' => 'url'));
      $this->stats = $this->get_stats();
      $this->teams_timezones = [];
      
      $this->roster();
    }
    
    // Info
      private function get_stats() {
        global $pxl, $wpdb;
        
        $season = isset($_GET['season']) ? $_GET['season'] : $pxl->season['number'];
        // TODO fix 1000
        $sql = $wpdb->prepare("
          SELECT sum(r1.mmr) + 1000 as mmr, count(r1.matches) as matches, sum(r1.wins) as wins, count(r1.matches) - sum(r1.wins) as losses
          FROM (
            SELECT t1.name, sum(t1.mmr) as mmr, count(DISTINCT t1.matchID) as matches, t2.wins
            FROM dl_teams AS t1
            LEFT JOIN (
              SELECT team_id, name, matchID,
              CASE
                WHEN sum(outcome) = 2 THEN 1
                WHEN game_id = 'forfeit' THEN 1
                ELSE 0
              END as wins
              FROM dl_teams
              WHERE season = %d
              GROUP BY team_id, matchID
              ORDER BY name ASC
            ) as t2 on t1.matchID = t2.matchID AND t2.team_id = t1.team_id
            WHERE t2.team_id = {$this->id}
            GROUP BY t1.team_id, t1.matchID
            ORDER BY name ASC
          ) as r1
          GROUP BY r1.name
          ORDER BY MMR DESC
        ", $season);
        
        $items = $wpdb->get_results($sql);
        
        return $items;
      }
      
    // Roster
      private function roster() {
        $this->roster = array(
          'captains'   => array(),
          'players'    => array(),
          'error'      => array(),
          'requesting' => array(),
        );
        
        $this->roster_get('captains');
        $this->roster_get('players');
        
        $this->roster_validate();
      }
      private function roster_get( $type ) {
        if ( $players = get_field($type, $this->id) ) {
          $players = array_column($players, 'post_title', 'ID');
          
          if ( $type == 'players' ) asort($players);
          
          foreach ($players as $player_id => $name) {
            if ( $discord = get_post_meta($player_id, 'discord_username', true) ) {
              $this->roster[$type][$discord] = array(
                'ID'           => $player_id,
                'name'         => $name,
                'discord'      => $discord,
                'gamer_id'     => get_post_meta($player_id, 'gamer_id', true),
                'gamer_id_alt' => get_post_meta($player_id, 'gamer_id_alt', true),
              );
            }
          }
        }
      }
      private function roster_validate() {
        $users = new WP_User_Query(array(
          'meta_query' => array(
            array(
              'key'     => 'dl_team',
              'compare' => '=',
              'value'   => $this->id,
              'type'    => 'NUMERIC'
            )
          ),
          'fields' => array('ID', 'display_name', 'user_nicename')
        ));
        
        $players = array();
        
        foreach ($users->results as $key => $user) {
          $profile = get_posts(array(
            'post_type' => 'player',
            'meta_query' => array(
              array(
                'key'     => 'discord_username',
                'compare' => '=',
                'value'   => html_entity_decode($user->display_name)
              )
            )
          ));
          
          $players[html_entity_decode($user->display_name)] = isset($profile[0]) ? $profile[0]->post_title : $user->user_nicename;
        }
        
        $discord    = array_keys($this->roster['players']);
        $remove     = array_diff($discord, array_keys($players));
        $requesting = array_diff(array_keys($players), $discord);
        
        $this->roster['requesting'] = array_filter($players, function($player, $discord) use($requesting) {
          return in_array($discord, $requesting);
        }, ARRAY_FILTER_USE_BOTH);
        
        foreach ($remove as $username) {
          $this->roster['error'][$username] = $this->roster['players'][$username];
          unset($this->roster['captains'][$username]);
          unset($this->roster['players'][$username]);
        }
        
        $this->roster['spots'] = 12 - count($this->roster['players']) - count($this->roster['error']);
      }
      
    // Timezones
      public function timezones() {
        global $wpdb;
        
        $this->wpdb = $wpdb;
        
        $this->offset_servers = [
          -8 => 'San Jose / Oregon',
          -6 => 'Dallas / Iowa',
          -5 => 'New York / N. Carolina',
          1  => 'Amsterdam / Frankfurt',
          8  => 'Hong Kong / Singapore',
          11 => 'Sydney'
        ];
        $this->servers_offset = array_flip($this->offset_servers);
        
        $players           = array_column($this->roster['players'], 'ID', 'name');
        $discord_usernames = array_column($this->roster['players'], 'discord', 'ID');
        
        if ( $users_ids = $this->users_ids($discord_usernames) ) {
          $discord_players = $this->discord_players($players, $discord_usernames);
          $this->userids_players = $this->users_players($users_ids, $discord_players);
          $this->timezones = $this->users_timezones($users_ids);
        }
      }
      public function servers() {
        if ( empty($this->timezones) ) $this->timezones();
        
        $this->ideal_servers = $this->team_servers($this->timezones);
      }
      public function discord_players( $players, $discord_usernames ) {
        $discord_players = [];
      
        $players = array_flip($players);
      
        foreach ($discord_usernames as $player_id => $discord_username) {
          if ( $player_name = $players[$player_id] ) {
            $discord_players[$discord_username] = $player_name;
          }
        }
      
        return $discord_players;
      }
      public function discord_usernames( $players ) {
        $player_ids = implode(', ', array_values($players));
      
        $discord_usernames = $this->wpdb->get_results("
          SELECT post_id, meta_value
          FROM {$this->wpdb->prefix}postmeta
          WHERE meta_key = 'discord_username' AND post_id IN ({$player_ids})
          ORDER BY post_id ASC
        ");
      
        return array_column($discord_usernames, 'meta_value', 'post_id');
      }
      public function users_ids( $discord_usernames ) {
        $discord_usernames = implode('", "', $discord_usernames);
      
        $users_ids = $this->wpdb->get_results("
          SELECT user_id, meta_value
          FROM {$this->wpdb->prefix}usermeta
          WHERE meta_key = 'discord' AND meta_value IN (\"{$discord_usernames}\")
        ");
      
        $users_ids = !empty($users_ids) ? array_column($users_ids, 'user_id', 'meta_value') : FALSE;
      
        return $users_ids;
      }
      public function users_players( $users_ids, $discord_players ) {
        $users_players = [];
        
        foreach ($users_ids as $discord_username => $user_id) {
          if ( isset($discord_players[$discord_username]) ) {
            $player = $discord_players[$discord_username];
            $users_players[$user_id] = $player;
          }
        }
        
        return $users_players;
      }
      public function users_timezones( $users_ids ) {
        $users_ids = implode(', ', $users_ids);
        
        $timezones = $this->wpdb->get_results("
          SELECT 
            CASE
              WHEN meta_value = '' THEN 'NOT SPECIFIED'
              ELSE meta_value
            END as timezone,
            COUNT(umeta_id) as total,
            GROUP_CONCAT(user_id SEPARATOR ', ' ) as users_ids
          FROM {$this->wpdb->prefix}usermeta AS um
          WHERE meta_key = 'dl_timezone' AND user_id IN ({$users_ids})
          GROUP BY meta_value
          ORDER BY total DESC
        ", ARRAY_A);
        
        foreach ($timezones as $key => $tz) {
          if ( 'NOT SPECIFIED' == $tz['timezone'] ) {
            unset($timezones[$key]);
            continue;
          }
          
          $timezones[$key]['offset'] = $this->offset($tz['timezone']);
          
          $users   = explode(', ', $tz['users_ids']);
          $timezones[$key]['players'] = [];
          
          foreach ($users as $user_id) {
            $player = isset($this->userids_players[$user_id]) ? $this->userids_players[$user_id] : FALSE;
            if ( $player ) {
              $timezones[$key]['players'][] = $player;
              sort($timezones[$key]['players']);
            }
          }
        }
        
        return $timezones;
      }
      public function team_servers( $timezones ) {
        $ideal   = [];
        $servers = [];
        $keys    = array_keys($this->offset_servers);
        
        rsort($keys);
        
        foreach ($timezones as $tz) {
          if ( 'NOT SPECIFIED' == $tz['timezone'] ) continue;
          
          $offset = $this->offset($tz['timezone']);
          if ( !isset($this->offset_servers[$offset]) ) $offset = $this->offset_closest($offset, $keys);
          
          $server = isset($this->offset_servers[$offset]) ? $this->offset_servers[$offset] : FALSE;
          
          if ( $server ) {
            if ( !isset($ideal[$server]) ) $ideal[$server] = 0;
            $ideal[$server] += $tz['total'];
          }
        }
        
        arsort($ideal);
        
        $current_score = FALSE;
        foreach ($ideal as $server => $score) {
          if ( count($servers) < 2 ) {
            $current_score = $score;
            if ( $score > 3 ) $servers[] = "{$server} ({$score})";
            else if ( $score >= 2 ) $servers[] = "{$server} ({$score})";
          }
          else if ( $score > 2 && $score === $current_score ) {
            $servers[] = "{$server} ({$score})";
          }
        }
        
        if ( count($servers) < 2 ) {
          $offset = explode(' (', $servers[0]);
          $offset = $this->offset_closest($this->servers_offset[$offset[0]], $keys, 'nomatch');
          $server = isset($this->offset_servers[$offset]) ? $this->offset_servers[$offset] : FALSE;
          $servers[] = $server;
        }
        
        return $servers;
      }
      
      private function offset( $timezone ) {
        if ( $timezone == 'UTC' ) $offset = 0;
        elseif ( strpos($timezone, 'UTC') > -1 ) {
          $offset = explode('UTC', $timezone);
          $offset = $offset[1];
        }
        else {
          $time = new \DateTime('now', new DateTimeZone($timezone));
          $offset = $time->format('P');
          $offset = explode(':', $offset);
          $offset = $offset[0];
        }
        
        $offset = str_replace(array('-0', '+', '00'), array('-', '', '0'), $offset);
        
        return $offset;
      }
      private function offset_closest($search, $arr, $exact = TRUE ) {
        $closest = null;
        
        $arr = array_reverse($arr);
        
        foreach ($arr as $item) {
          $abs = abs($search - $closest) > abs($item - $search);
          
          if ( $closest === null || abs($search - $closest) > abs($item - $search) ) {
            
            if ( $exact === 'nomatch' && $search === $item ) continue;
            
            $closest = $item;
          }
        }
      
        return $closest;
      }
      
    // Helpers
      public function table( $data, $return = FALSE ) {
        $fn = "table_{$data}";
        
        if ( $return ) ob_start();
        $this->$fn();
        if ( $return ) return ob_get_clean();
      }
      public function table_timezones() {
        $rows = [];
        
        foreach ($this->timezones as $tz) {
          $offset = $this->offset($tz['timezone']);
          
          $rows[] = sprintf(
            ' <tr>
                <td align="left">%s</td>
                <td>%s</td>
                <td align="center">%s</td>
                <td>%s</td>
              </tr>
            ',
            $tz['timezone'],
            intval($offset),
            $tz['total'],
            implode(', ', $tz['players'])
          );
        }
        
        printf(
          '
            <div class="team">
              <table>
                <thead>
                  <tr>
                    <th width="255px">Timezone</th>
                    <th width="90px">UTC Offset</th>
                    <th width="90px">Total</th>
                    <th>Players</th>
                  </tr>
                </thead>
                <tbody>
                  %s
                </tbody>
              </table>
            </div>
          ',
          implode("\r\n", $rows)
        );
      }
      public function table_servers() {
        printf(
          '
            <div class="team">
              <table>
                <thead>
                  <tr>
                    <th>Your Team\'s Ideal Servers</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>%s</td></tr>
                </tbody>
              </table>
            </div>
          ',
          implode("<br/>", $this->ideal_servers)
        );
      }
  }
}
