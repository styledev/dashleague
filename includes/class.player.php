<?php if ( !class_exists('dlPlayer') ) {
  class dlPlayer {
    function __construct( $user = FALSE ) {
      
      $this->team = array(
        'active'   => FALSE,
        'captain'  => FALSE,
        'rostered' => FALSE,
        'status'   => FALSE,
      );
      
      $this->user = $user ? $user : wp_get_current_user();
      
      if ( $this->user ) $this->init();
    }
      
    // Player Status
      public function init() {
        $this->player();
        $this->captain();
        $this->rostered();
        $this->status();
        
        $this->team['active'] = $this->team['status'] == $this->team['rostered'];
      }
      private function captain() {
        $captain = array();
        
        if ( $this->player ) {
          $captain = get_posts(array(
            'post_type' => 'team',
            'meta_query' => array(
              array(
                'key'     => 'captains',
                'compare' => 'LIKE',
                'value'   => '"' . $this->player->ID . '"',
              )
            )
          ));
        }
        
        $this->team['captain'] = !empty($captain) ? $captain[0] : FALSE;
      }
      private function player() {
        $discord = $this->user->get('discord');
        $player  = array();
        
        if ( $discord ) {
          $player = get_posts(array(
            'post_type' => 'player',
            'meta_query' => array(
              array(
                'key'     => 'discord_username',
                'compare' => '=',
                'value'   => $discord
              )
            )
          ));
        }
        
        $this->player = !empty($player) ? $player[0] : FALSE;
      }
      private function status() {
        $this->team['status'] = $this->user->get('dl_team');
        
        if ( is_numeric($this->team['status']) ) {
          $this->team['status'] = get_post($this->team['status']);
        }
      }
      private function rostered() {
        $rostered = array();
        
        if ( $this->player ) {
          $rostered = get_posts(array(
            'post_type' => 'team',
            'meta_query' => array(
              array(
                'key'     => 'players',
                'compare' => 'LIKE',
                'value'   => '"' . $this->player->ID . '"',
              )
            )
          ));
        }
        
        $this->team['rostered'] = !empty($rostered) ? $rostered[0] : FALSE;
      }
      
    // Helpers
      
  }
}
