<?php if ( !class_exists('teamup') ) {
  class teamup {
    function __construct() {
      // https://apidocs.teamup.com/#teamup-api-overview
      
      $this->calendars = array( 'matches' => 'kst2y7dkgo3npmnz8m' );
      
      $this->cache = 60 * 30;
      $this->key   = '1d0bef59e9fe74044578cf72936fe209c526be2805c2c533e81cf887af2feb9d';
      $this->url   = 'https://api.teamup.com/';
    }
    
    // Functions
      public function events_upcoming() {
        $id    = 'teamup_events';
        $clear = is_user_logged_in() && (isset($_GET['clear']) && $_GET['clear'] == 'cache');
        
        if ( $clear ) {
          delete_option($id);
          delete_option("{$id}_cached_on");
        }
        
        if ( !$clear && $this->cached($id) ) {
          if ( $items = get_option($id) ) return $items;
        }
        
        $url = $this->url . $this->calendars['matches'] . '/events';
        
        $start_date = new DateTime();
        $start_date->sub(new DateInterval('P14D'));
        
        $end_date = new DateTime();
        $end_date->add(new DateInterval('P14D'));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$url?startDate={$start_date->format('Y-m-d')}&endDate={$end_date->format('Y-m-d')}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "Content-Type: application/json",
          "Teamup-Token: {$this->key}",
        ]);
        
        $response = curl_exec($ch);
        
        try {
          $items = json_decode($response);
          $items = $items->events;
        } catch ( Exception $ex ) {
          $items = array();
        }
        
        update_option($id, $items);
        update_option("{$id}_cached_on", time());
        
        return $items;
      }
      public function matches_upcoming() {
        global $pxl;
        
        if ( !isset($pxl->teamup) ) $pxl->teamup = new teamup();
        
        $upcoming = array_reverse($pxl->teamup->events_upcoming());
        $offset   = get_option('gmt_offset'); if ( $offset == 0 ) $offset = "+0";
        $tz       = new DateTimeZone($offset);
        $now      = new DateTime("now", $tz);
        $day      = $now->format('d');
        
        $events = array(
          'Matches Today'    => array(),
          'Matches Tomorrow' => array(),
          'Upcoming Matches' => array(),
          'Past Matches'     => array(),
        );
        
        $user    = wp_get_current_user();
        $user_tz = $user->ID ? get_user_meta($user->ID, 'dl_timezone', TRUE) : 'America/New_York';
        
        if ( strpos($user_tz, 'UTC') > -1 ) $user_tz = str_replace('UTC', '', $user_tz);
        
        foreach ($upcoming as $event) {
          if ( strlen($event->start_dt) != 25 ) $event->start_dt .= '-04:00';
          
          $start = DateTime::createFromFormat("Y-m-d\TH:i:sT", $event->start_dt);
          $start->setTimeZone(new DateTimeZone($user_tz));
          
          $diff    = date_diff($now, $start);
          $network = isset($event->custom->casting_network) ? $event->custom->casting_network[0] : FALSE;
          $stream  = isset($event->custom->stream_link) ? $event->custom->stream_link : FALSE;
          
          switch ($network) {
            case 'dltv': $network_link = 'https://www.youtube.com/c/DashLeagueTV'; break;
            case 'dln': $network_link  = 'https://www.youtube.com/c/DashLeagueNetwork'; break;
            default: $network_link     = FALSE; break;
          }
          
          $tense = $start->format('d') == $day ? 'Live' : 'Watch';
          $link  = $stream ? $stream : $network_link;
          
          if ( $link ) $link = sprintf('<a href="%s" class="btn btn--%s" target="_blank">%s @ %s</a>', $link, $network, $tense, strtoupper($network));
          else $link = sprintf('<button class="btn btn--ghost btn--none">%s</button>', ($diff->invert ? 'Not Casted' : 'May not be Casted'));
          
          $servers = isset($event->custom->servers) ? str_replace('_', ' ', implode(', ', $event->custom->servers)) : 'Not Specified';
          $teams   = explode(' ', $event->title);
          
          $team_a      = new WP_Query([ 'posts_per_page' => 1, 'post_type' => 'team', 'title' => $teams[0] ]);
          $team_a      = $team_a->post;
          $team_a_logo = pxl::image($team_a, array( 'w' => 75, 'h' => 75, 'return' => 'tag' ));
          
          $team_b      = new WP_Query([ 'posts_per_page' => 1, 'post_type' => 'team', 'title' => $teams[2] ]);
          $team_b      = $team_b->post;
          $team_b_logo = pxl::image($team_b, array( 'w' => 75, 'h' => 75, 'return' => 'tag' ));
          
          if ( isset($teams[3]) && $teams[3] == '(cancelled)' || ( isset($event->custom->status) && $event->custom->status[0] = 'cancelled' ) ) $link = sprintf('<button class="btn btn--ghost btn--none">%s</button>', 'Cancelled');
          
          if ( isset($event->custom->status) ) {
            $link = sprintf('<button class="btn btn--ghost btn--none">%s</button>', $event->custom->status[0]);
          }
          
          $versus = $team_a_logo && $team_b_logo ? sprintf('
            <div class="event__vs">
              <a href="%s">%s</a>
              <span>VS</span>
              <a href="%s">%s</a>
            </div>',
            (isset($team_a->ID) ? get_permalink($team_a->ID) : ''), $team_a_logo,
            (isset($team_b->ID) ? get_permalink($team_b->ID) : ''), $team_b_logo
          ) : '';
          
          $match = sprintf('
            <div class="event">
              %s
              <div class="event__details">
                <div class="event__title">
                  %s
                  <span>vs</span>
                  %s
                </div>
                <div class="event__datetime" data-date="%s" data-user="%d">
                  <span class="event__date">%s</span> @
                  <span class="event__time">%s</span>
                </div>
              </div>
              <div class="event__actions">
                %s
              </div>
            </div>',
            $versus,
            $teams[0], $teams[2],
            $event->start_dt, $user->ID,
            $start->format('F jS, Y'), $start->format('g:ia T'),
            $link
          );
          
          if ( $start->format('d') == $day ) {array_push($events['Matches Today'], $match);}
          else if ( $start->format('d') - $day == 1 ) array_push($events['Matches Tomorrow'], $match);
          else if ( !$diff->invert ) array_push($events['Upcoming Matches'], $match);
          // else if ( $diff->invert ) array_push($events['Past Matches'], $match);
        }
        
        return $events;
      }
      
    // Helper
      function cached( $option_name ) {
        $now       = time();
        $cached_on = (int)get_option("{$option_name}_cached_on");
        $elapsed   = $now - $cached_on;
        
        return $elapsed <= $this->cache;
      }
  }
}
