<?php
  global $wpdb;
  
  class teams_timezones{
    private $offset_servers, $servers, $servers_offset, $teams, $teams_players, $teams_timezones, $wpdb;
    function __construct() {
      global $wpdb;
      
      $this->wpdb = $wpdb;
      
      $this->teams = get_posts([
        'post_type'      => 'team',
        'posts_per_page' => -1,
        'order'          => 'ASC',
        'orderby'        => 'title',
        'tax_query'      => array(
          array(
            'taxonomy' => 'season',
            'field'    => 'slug',
            'terms'    => 'current',
          )
        ),
        // 'post__in' => [1679, 917]
      ]);
      
      $this->servers = [
        'Amsterdam'   => 1,
        'Dallas'      => -6,
        'Frankfurt'   => 1,
        'Hong Kong'   => 8,
        'Iowa'        => -6,
        'N. Carolina' => -5,
        'New York'    => -5,
        'Oregon'      => -8,
        'San Jose'    => -8,
        'Singapore'   => 8,
        'Sydney'      => 11,
      ];
      
      $this->offset_servers = [
        -8 => 'San Jose / Oregon',
        -6 => 'Dallas / Iowa',
        -5 => 'New York / N. Carolina',
        1  => 'Amsterdam / Frankfurt',
        8  => 'Hong Kong / Singapore',
        11 => 'Sydney'
      ];
      $this->servers_offset = array_flip($this->offset_servers);
      
      $this->teams_players   = [];
      $this->teams_timezones = [];
    }
    public function data() {
      foreach ($this->teams as $team) {
        if ( $players = get_field('players', $team) ) {
          $players    = array_column($players, 'ID', 'post_title'); asort($players);
          
          $discord_usernames = $this->discord_usernames($players);
          $discord_players   = $this->discord_players($players, $discord_usernames);
          
          if ( $users_ids = $this->users_ids($discord_usernames) ) {
            $users_players = $this->users_players($users_ids, $discord_players);
            $this->teams_players = array_replace($this->teams_players, $users_players);
            $this->teams_timezones[$team->post_title] = $this->users_timezones($users_ids);
          }
        }
      }
    }
    public function data_tables() {
      foreach ($this->teams_timezones as $team => $timezones) {
        $rows = [];
        
        $team_utc_score = 0;
        $team_players   = 0;
        $team_servers   = $this->team_servers($timezones);
        
        foreach ($timezones as $tz) {
          if ( 'NOT SPECIFIED' == $tz->timezone ) continue;
          
          $players = [];
          $users   = explode(', ', $tz->users_ids);
          
          foreach ($users as $user_id) {
            $player = isset($this->teams_players[$user_id]) ? $this->teams_players[$user_id] : FALSE;
            if ( $player ) $players[] = $player;
          }
          
          $offset = $this->offset($tz->timezone);
          
          $rows[] = sprintf(
            ' <tr>
                <td>%s</td>
                <td>%s</td>
                <td align="center">%s</td>
                <td>%s</td>
              </tr>
            ',
            $tz->timezone,
            intval($offset),
            $tz->total,
            implode(', ', $players)
          );
        }
        
        printf(
          '
            <div class="team">
              <table>
                <thead>
                  <tr>
                    <th width="100px">Team</th>
                    <th width="255px">Best Servers</th>
                    <th width="255px">Timezone</th>
                    <th width="90px">UTC Offset</th>
                    <th width="90px">Total</th>
                    <th>Players</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td align="center" rowspan="%d">%s</td><td align="center" rowspan="%1$d">%s</td></tr>
                  %s
                </tbody>
              </table>
            </div>
          ',
          count($rows) + 1,
          $team,
          implode("<br>", $team_servers),
          implode("\r\n", $rows)
        );
      }
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
      
      return $this->wpdb->get_results("
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
      ");
    }
    public function team_servers( $timezones ) {
      $ideal   = [];
      $servers = [];
      $keys    = array_keys($this->offset_servers);
      
      rsort($keys);
      
      foreach ($timezones as $tz) {
        if ( 'NOT SPECIFIED' == $tz->timezone ) continue;
        
        $offset = $this->offset($tz->timezone);
        if ( !isset($this->offset_servers[$offset]) ) $offset = $this->offset_closest($offset, $keys);
        
        $server = isset($this->offset_servers[$offset]) ? $this->offset_servers[$offset] : FALSE;
        
        if ( $server ) {
          if ( !isset($ideal[$server]) ) $ideal[$server] = 0;
          $ideal[$server] += $tz->total;
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
        if ( $closest === null || abs($search - $closest) > abs($item - $search) ) {
          
          if ( $exact === 'nomatch' && $search === $item ) continue;
          
          $closest = $item;
        }
      }
      
      return $closest;
    }
  }
  
  $tz = new teams_timezones();
  $tz->data();
?>
<style>
  .team+.team{margin-top:3em;}
  table{border-collapse:collapse;width:100%;}
  th,td{border:1px solid #ccc;padding:0.25rem;}
  th{white-space:nowrap;}
  td[align="center"]{text-align:center;}
  tbody tr:nth-child(odd){background:#eee;}
  .nofill{pointer-events:none;}
  tfoot tr td{background-color:#ffd5d5!important;color:red;font-weight:bold;font-size:larger;text-align:left;}
</style>
<div class="wp-block-group alignfull">
  <div class="wp-block-group__inner-container">
    <div class="list alignwide">
      <?php $tz->data_tables(); ?>
    </div>
  </div>
</div>