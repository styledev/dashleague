<?php if ( !class_exists('dlTeam') ) {
  class dlTeam {
    function __construct( $team = FALSE ) {
      if ( !$team ) {
        global $post;
        $team = $post;
      }
      
      $this->id         = $team->ID;
      $this->name       = $team->post_title;
      $this->link       = get_permalink($team->ID);
      $this->logo       = pxl::image($this->id, array('w' => 256, 'h' => 256, 'return' => 'url'));
      $this->stats      = $this->get_stats();
      
      $this->roster();
    }
    
    // Info
      private function get_stats() {
        global $pxl, $wpdb;
        
        $season = isset($_GET['season']) ? $_GET['season'] : $pxl->season['number'];
        
        $sql = $wpdb->prepare("
          SELECT sum(r1.rank_gain) as mmr, count(r1.matches) as matches, sum(r1.wins) as wins, count(r1.matches) - sum(r1.wins) as losses
          FROM (
            SELECT t1.name, sum(t1.rank_gain) as rank_gain, count(DISTINCT t1.matchID) as matches, t2.wins
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
                'ID'      => $player_id,
                'name'    => $name,
                'discord' => $discord,
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
        
        $discord    = array_keys($this->roster['players']);
        $players    = array_column($users->results, 'user_nicename', 'display_name');
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
      
    // Helpers
      
  }
}
