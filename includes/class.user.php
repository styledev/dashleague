<?php if ( !class_exists('dlUser') ) {
  class dlUser {
    private $user;
    function __construct( $user = FALSE ) {
      $this->active  = FALSE;
      $this->captain = FALSE;
      $this->user    = $user ? $user : wp_get_current_user();
      $this->team    = FALSE;
      
      if ( $this->user ) $this->init();
      
      // fns::put($this);
    }
      
    // Player Details
      public function init() {
        $this->get_profile();
        $this->get_team();
        
        $this->active  = is_object($this->team) && isset($this->team->roster['players'][$this->discord]);
        $this->captain = is_object($this->team) && isset($this->team->roster['captains'][$this->discord]);
      }
      private function get_profile() {
        $this->profile = FALSE;
        
        if ( $this->discord = $this->user->get('discord') ) {
          $profile = get_posts(array(
            'post_type' => 'player',
            'meta_query' => array(
              array(
                'key'     => 'discord_username',
                'compare' => '=',
                'value'   => $this->discord
              )
            )
          ));
          
          if ( !empty($profile) ) {
            $player = $profile[0];
            
            $this->profile = array(
              'ID'      => $player->ID,
              'name'    => $player->post_title,
            );
          }
        }
      }
      private function get_team() {
        $this->team = $this->user->get('dl_team');
        
        if ( is_numeric($this->team) ) {
          if ( $team = get_post($this->team) ) {
            $this->team = new dlTeam($team);
          }
        }
      }
      
    // Helpers
      
  }
}
