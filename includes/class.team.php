<?php if ( !class_exists('dlTeam') ) {
  class dlTeam {
    protected $id, $roster;
    function __construct( $team ) {
      $this->id       = $team->ID;
      $this->name     = $team->post_title;
      $this->link     = get_permalink($team->ID);
      $this->logo     = pxl::image($this->id, array('return' => 'url'));
      $this->stats    = $this->get_stats();
      $this->captains = $this->get_captains();
      $this->players  = $this->get_players();
      // $this->roster   = $this->get_roster();
    }
    
    // Player Status
      private function get_captains() {
        if ( $captains = get_field('captains', $this->id) ) {
          $captains = array_column($captains, 'post_title', 'ID');
          sort($captains);
          return $captains;
        }
        else return array();
      }
      private function get_players() {
        if ( $players = get_field('players', $this->id) ) {
          $players = array_column($players, 'post_title', 'ID');
          sort($players);
          return $players;
        }
        else return array();
      }
      private function get_playersx() {
        $players = array();
        
        $users = new WP_User_Query(array(
          'meta_query' => array(
            array(
              'key'     => 'dl_team',
              'compare' => '=',
              'value'   => $this->id,
              'type'    => 'NUMERIC'
            )
          )
        ));
        
        foreach ($users->results as $key => $user) {
          $player = new dlPlayer($user);
          
          array_push($players, array(
            'id'        => $user->ID,
            'name'      => $user->data->display_name,
            'player'    => $player->player ? $player->player->post_title : NULL,
            'player_id' => $player->player ? $player->player->ID : NULL,
            'team'      => $player->team['status']->post_title,
            'team_id'   => $player->team['status']->ID,
          ));
        }
        
        return $players;
      }
      private function get_roster() {
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
        
        $roster = array(
          'roster'     => array(),
          'requesting' => array(),
        );
        
        return $users;
      }
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
      
    // Helpers
      
  }
}
