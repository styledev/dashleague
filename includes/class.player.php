<?php if ( !class_exists('dlPlayer') ) {
  class dlPlayer {
    function __construct( $player = FALSE ) {
      
      $this->id = $player ? $player->ID : get_the_ID();
      
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
      
      // fns::put($teams);die;
      
      $this->active  = FALSE;
      $this->captain = FALSE;
      $this->team    = FALSE;
      
      
      
      $this->init();
      
      fns::put($this);
      die;
    }
      
    // Player Details
      public function init() {
        // $this->get_profile();
        // $this->get_team();
        
        // $this->active  = is_object($this->team) && isset($this->team->roster['players'][$this->discord]);
        // $this->captain = is_object($this->team) && isset($this->team->roster['captains'][$this->discord]);
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
