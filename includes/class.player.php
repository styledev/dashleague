<?php if ( !class_exists('dlPlayer') ) {
  class dlPlayer {
    function __construct( $player = FALSE ) {
      $this->id = $player ? $player->ID : get_the_ID();
      $this->discord = get_field('discord_username', $this->id);
      
      $this->init();
    }
    
    // Player Details
      public function init() {
        $this->get_team();
      }
      
    // Helpers
      public function get_matches() {
        global $wpdb;
        
        $this->matches = $wpdb->get_results($wpdb->prepare("SELECT * FROM dl_players WHERE player_id = %d ORDER BY datetime DESC", $this->id));
      }
      public function get_team() {
        $teams = get_posts(array(
          'post_type' => 'team',
          'meta_query' => array(
            array(
              'key'     => 'players',
              'value'   => '"' . $this->id . '"',
              'compare' => 'LIKE'
            )
          )
        ));
        
        $this->team    = isset($teams[0]) ? new dlTeam($teams[0]) : FALSE;
        $this->active  = isset($this->team->roster['players'][$this->discord]);
        $this->captain = isset($this->team->captains['players'][$this->discord]);
      }
      public function get_stats() {
        if ( !isset($this->matches) ) $this->get_matches();
        
        $this->stats = array(
          'kills'  => array_sum(array_column($this->matches, 'kills')),
          'deaths' => array_sum(array_column($this->matches, 'deaths')),
          'score'  => array_sum(array_column($this->matches, 'score')),
          'wins'   => array_sum(array_column($this->matches, 'outcome')),
          'time'   => 0,
        );
        
        $this->stats['kd'] = $this->stats['deaths'] > 0 ? round($this->stats['kills'] / $this->stats['deaths'], 2) : 'N/A';
      }
  }
}
