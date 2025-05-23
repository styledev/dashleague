<?php if ( !class_exists('theme') ) {
  include 'class.config.php';
  include 'class.api.php';
  include 'class.stats.php';
  include 'class.user.php';
  include 'class.player.php';
  include 'class.team.php';
  include 'class.teamup.php';
  
  class theme {
    public $api, $teamup;
    private $tml_errors, $config, $template = FALSE, $templates;
    public
      $cycle,
      $season,
      $season_dates = FALSE,
      $servers      = [
        'Amsterdam / Frankfurt'     => 'Amsterdam / Frankfurt',
        'Dallas / Iowa'             => 'Dallas / Iowa',
        'Hong Kong / Singapore'     => 'Hong Kong / Singapore',
        'New York / North Carolina' => 'New York / North Carolina',
        'Oregon / San Jose'         => 'Oregon / San Jose',
        'Sydney'                    => 'Sydney',
      ],
      $stats;
    public $instagram, $twitter;
    function __construct( &$blocks, &$menus, &$post_types, &$resources, &$sidebars, &$taxonomies, &$custom ) {
      $menus = array(
        'mobile'     => 'Mobile',
        'main-left'  => 'Main Left',
        'main-right' => 'Main Right',
        'top-left'   => 'Top Left',
        'top-right'  => 'Top Right',
        'footer'     => 'Footer',
      );
      
      $season = get_field('seasons', 'options');
      $this->season = array_pop($season);
      
      $season_query = isset($_GET['list_season']) ? $_GET['list_season'] : 'current';
      
      $post_types = array(
        'map'      => array(
          'menu_icon'   => 'dashicons-location-alt',
          'query_admin' => array('order' => 'ASC', 'orderby' => 'title')
        ),
        'player'   => array(
          'menu_icon'   => 'dashicons-universal-access-alt',
          'query'       => array('order' => 'ASC', 'orderby' => 'title', 'posts_per_page' => -1, 'season' => $season_query),
          'query_admin' => array('order' => 'ASC', 'orderby' => 'title'),
          'supports'    => array('title', 'editor'),
        ),
        'template' => array(
          'menu_icon' => 'dashicons-portfolio',
          'supports'  => array('title', 'editor', 'page-attributes'),
          'public'    => FALSE,
          'show_ui'   => TRUE,
          'options'   => FALSE,
        ),
        'team'     => array(
          'menu_icon'   => 'dashicons-groups',
          'query'       => array('order' => 'ASC', 'orderby' => 'title', 'posts_per_page' => -1, 'season' => $season_query),
          'query_admin' => array('order' => 'ASC', 'orderby' => 'title'),
        ),
      );
      
      $taxonomies = array(
        'season' => array(
          'object_type' => array('player', 'team')
        )
      );
      
      $resources = array(
        'min' => 'live',
        'css' => array(
          'datatables.min' => array('enqueue' => FALSE),
          'gf'             => array('enqueue' => FALSE),
          'magnific-popup' => array('enqueue' => FALSE),
          'swiper.min'     => array('enqueue' => FALSE),
          'team'           => array('enqueue' => FALSE),
          'base'           => TRUE,
          'theme'          => array('deps' => array('base')),
        ),
        'js'  => array(
          'datatables.min' => array('enqueue' => FALSE),
          'imask.min'      => array('enqueue' => FALSE, 'in_footer' => TRUE),
          'api'            => array('in_footer' => FALSE, 'localize' => array('nonce' => wp_create_nonce('wp_rest')), 'enqueue' => FALSE),
          'api-account'    => array('in_footer' => FALSE, 'deps' => array('api'), 'enqueue' => FALSE),
          'api-manage'     => array('in_footer' => FALSE, 'deps' => array('api'), 'enqueue' => FALSE),
          'api-form-stats' => array('in_footer' => FALSE, 'deps' => array('api'), 'enqueue' => FALSE),
          'register'       => array('in_footer' => TRUE, 'localize' => array('nonce' => wp_create_nonce('wp_rest')), 'enqueue' => FALSE),
          'icons'          => array('src' => 'https://kit.fontawesome.com/89a294a8ae.js', 'crossorigin' => 'anonymous', 'enqueue' => TRUE),
          'magnific-popup' => array('enqueue' => FALSE, 'in_footer' => TRUE),
          'modal.min'      => array('deps' => array('magnific-popup'), 'enqueue' => FALSE, 'in_footer' => TRUE),
          'swiper.min'     => array('enqueue' => FALSE),
          'site'           => array('deps' => array('jquery', 'acf-input')),
          'touch-punch.min' => array('enqueue' => TRUE),
        ),
      );
      
      $custom['sizes'] = array(
        '1600xauto@2x',
        '370x150@2x',
      );
      
      $this->config = new config();
      $this->gutenberg();
      
      $this->api   = new dlAPI($custom['endpoints']);
      $this->stats = new dlStats($custom['endpoints']);
    }
    
    // Gutenberg
      private function gutenberg() {
        // Styles
        if ( function_exists('register_block_style') ) {
          register_block_style('core/columns', array('name' => 'seamless', 'label' => 'Seamless', 'style_handle' => 'seamless'));
          register_block_style('core/cover', array('name' => 'full-height', 'label' => 'Full Height', 'style_handle' => 'full-height'));
          register_block_style('core/group', array('name' => 'iframe-max', 'label' => 'iFrame Max', 'style_handle' => 'iframe-max'));
          register_block_style('core/heading', array('name' => 'sub-heading', 'label' => 'Sub-Heading', 'style_handle' => 'sub-heading'));
          register_block_style('core/image', array('name' => 'ignore-max', 'label' => 'Ignore Max', 'style_handle' => 'ignore-max'));
          register_block_style('core/paragraph', array('name' => 'heading', 'label' => 'Heading', 'style_handle' => 'heading'));
          register_block_style('core/media-text', array('name' => 'media-last', 'label' => 'Media Last', 'style_handle' => 'media-last'));
        }
      }
      
    // Hooks : Actions
      public function actions() {
        add_action('edit_user_profile', array($this, 'action_show_user_profile'));
        add_action('edit_user_profile_update', array($this, 'action_update_profile_fields'));
        add_action('enqueue_block_editor_assets', array($this, 'action_enqueue_block_editor_assets'));
        add_action('init', array($this, 'action_init'));
        add_action('parse_query', array($this, 'action_parse_query'));
        add_action('personal_options_update', array($this, 'action_update_profile_fields'));
        add_action('profile_update', array($this, 'action_profile_update'), 10);
        add_action('show_user_profile', array($this, 'action_show_user_profile'));
        add_action('template_redirect', array($this, 'action_template_redirect'));
        add_action('user_register', array($this, 'tml_registration_save_form_fields'));
        add_action('wp', array($this, 'action_wp'), 5);
        add_action('wp_enqueue_scripts', array($this, 'action_wp_enqueue_scripts'), 90);
      }
      public function action_enqueue_block_editor_assets() {
        wp_enqueue_script('fontawesome', 'https://kit.fontawesome.com/570420dfdf.js', array('wp-blocks', 'wp-edit-post'));
      }
      public function action_init() {
        $this->tml_add_form_field();
        $this->tml_remove_form_field();
        
        add_rewrite_tag('%list_players%', '(players)');
        add_rewrite_tag('%list_season%', '(season-[^/]*)');
        
        add_rewrite_rule('^casting', 'index.php?page=&pagename=catch-all', 'top');
        add_rewrite_rule('teams/(season-[^/]*)$', 'index.php?post_type=team&list_season=$matches[1]', 'top');
      }
      public function action_parse_query() {
        global $pxlf;
        
        if ( $list_season = get_query_var('list_season') ) $pxlf->post_types['team']['query']['season'] = $list_season;
      }
      public function action_profile_update( $user_id ) {
        $user    = get_userdata($user_id);
        $discord = $user->get('discord');
        
        if ( isset($_POST['nickname']) && $discord ) {
          $profile = get_posts(array(
            'post_type' => 'player',
            'meta_query' => array(
              array(
                'key'     => 'discord_username',
                'compare' => '=',
                'value'   => $discord
              )
            )
          ));
          
          $player = isset($profile[0]) ? $profile[0] : FALSE;
          
          if ( $player ) {
            if ( $_POST['nickname'] != $player->post_title ) {
              if ( $this->season_dates['locked'] ) {
                $_POST['nickname'] = $player->post_title;
              }
              else {
                $player->post_title = $_POST['nickname'];
                $player->post_name = sanitize_title_with_dashes($_POST['nickname']);
                wp_update_post($player, FALSE, FALSE);
              }
            }
            if ( isset($_POST['gamer_id']) ) update_field('gamer_id', $_POST['gamer_id'], $player->ID);
            if ( isset($_POST['gamer_id_alt']) ) update_field('gamer_id_alt', $_POST['gamer_id_alt'], $player->ID);
          }
        }
        
        if ( isset($_POST['discord']) && !empty($_POST['discord']) && is_admin() ) {
          remove_action('profile_update', array($this, 'action_profile_update'), 10 );
          
          wp_update_user(array('ID' => $user_id, 'display_name' => $_POST['discord']));
          
          add_action( 'profile_update', array($this, 'action_profile_update'), 10 );
        }
      }
      public function action_show_user_profile( $user ) {
        if ( is_admin() ) {
          $server    = fns::array_to_attrs($this->servers, 'option', get_the_author_meta('dl_idealserver', $user->ID));
          $s_history = get_the_author_meta('dl_idealservers', $user->ID);
          $s_history = !empty($s_history) ? implode('</li><li>', $s_history) : 'None Yet';
          
          $timezones = wp_timezone_choice(get_the_author_meta('dl_timezone', $user->ID));
          
          $tz_history = get_the_author_meta('dl_timezones', $user->ID);
          $tz_history = !empty($tz_history) ? implode('</li><li>', $tz_history) : 'None Yet';
          
          printf('
              <table class="form-table">
                <tr>
                  <th><label for="discord">Discord Username</label></th>
                  <td>
                    <input type="text" id="discord" name="discord" value="%s" class="regular-text" />
                  </td>
                </tr>
                <tr>
                  <th><label for="gamer_id">Gamer ID</label></th>
                  <td>
                    <input type="text" id="gamer_id" name="gamer_id" value="%s" class="regular-text" />
                  </td>
                </tr>
                <tr>
                  <th><label for="gamer_id">Gamer ID (alt)</label></th>
                  <td>
                    <input type="text" id="gamer_id_alt" name="gamer_id_alt" value="%s" class="regular-text" />
                  </td>
                </tr>
                <tr>
                  <th><label for="dl_team">Team</label></th>
                  <td>
                    <select id="dl_team" name="dl_team">
                      %s
                    </select>
                  </td>
                </tr>
                <tr>
                  <th><label for="dl_team">Lowest Ping Server</label></th>
                  <td>
                    <select id="dl_idealserver" name="dl_idealserver">
                      %s
                    </select>
                  </td>
                </tr>
                <tr>
                  <th><label for="dl_team">Server History</label></th>
                  <td>
                    <ol>
                      <li>%s</li>
                    </ol>
                  </td>
                </tr>
                <tr>
                  <th><label for="dl_team">Timezone</label></th>
                  <td>
                    <select id="dl_timezone" name="dl_timezone">
                      %s
                    </select>
                  </td>
                </tr>
                <tr>
                  <th><label for="dl_team">Timezone History</label></th>
                  <td>
                    <ol>
                      <li>%s</li>
                    </ol>
                  </td>
                </tr>
              </table>
            ',
            esc_html(get_the_author_meta('discord', $user->ID)),
            esc_html(get_the_author_meta('gamer_id', $user->ID)),
            esc_html(get_the_author_meta('gamer_id_alt', $user->ID)),
            $this->dl_team_options($user),
            $server,
            $s_history,
            $timezones,
            $tz_history
          );
        }
      }
      public function action_template_redirect() {
        global $wp_query;
        
        if ( is_author() ) {
          $wp_query->set_404();
          status_header(404);
        }
      }
      public function action_update_profile_fields( $user_id ) {
        if ( !current_user_can('edit_user', $user_id) ) return false;
        
        if ( isset($_POST['discord']) && !empty($_POST['discord']) && is_admin() ) {
          $name = sanitize_text_field($_POST['discord']);
          
          $user = get_user_by('ID', $user_id);
          $user->data->display_name = $name;
          wp_update_user($user);
          
          update_user_meta($user_id, 'discord', sanitize_text_field($_POST['discord']));
        }
        
        if ( isset($_POST['gamer_id']) ) update_user_meta($user_id, 'gamer_id', sanitize_text_field($_POST['gamer_id']));
        
        if ( isset($_POST['gamer_id_alt']) ) update_user_meta($user_id, 'gamer_id_alt', sanitize_text_field($_POST['gamer_id_alt']));
        
        if ( isset($_POST['dl_team']) ) update_user_meta($user_id, 'dl_team', sanitize_text_field($_POST['dl_team']));
        
        if ( isset($_POST['dl_idealserver']) ) {
          $server  = sanitize_text_field($_POST['dl_idealserver']);
          $servers = get_user_meta($user_id, 'dl_idealservers', TRUE);
          
          if ( !$servers ) $servers = [];
          
          $key = sprintf('%s: %s', date('m/d/Y'), $server);
          
          $current = in_array($key, $servers);
          if ( !$current ) $servers[] = $key;
          
          update_user_meta($user_id, 'dl_idealserver', $server);
          update_user_meta($user_id, 'dl_idealservers', $servers);
        }
        
        if ( isset($_POST['dl_timezone']) ) {
          $timezone  = sanitize_text_field($_POST['dl_timezone']);
          $timezones = get_user_meta($user_id, 'dl_timezones', TRUE);
          
          if ( !$timezones ) $timezones = [];
          
          $key = sprintf('%s: %s', date('m/d/Y'), $timezone);
          
          $current = in_array($key, $timezones);
          if ( !$current ) $timezones[] = $key;
          
          update_user_meta($user_id, 'dl_timezone', $timezone);
          update_user_meta($user_id, 'dl_timezones', $timezones);
        }
        
        if ( isset($_POST['nickname']) ) {
          $name = sanitize_text_field($_POST['nickname']);
          wp_update_user(array('ID' => $user_id, 'first_name' => $name, 'nickname' => $name));
        }
      }
      public function action_wp() {
        if ( !is_admin() ) {
          global $wp_query;
          
          $this->templates = array(
            'footer'       => array(),
            'modal'        => array(),
            'post-content' => array(),
            'pre-content'  => array(),
          );
          
          $args = array(
            'meta_query'     => array(
              'relation' => 'OR',
              array('key' => 'targets', 'value' => "", 'compare' => '=' ),
            ),
            'order'          => 'ASC',
            'orderby'        => 'menu_order',
            'posts_per_page' => -1,
            'post_type'      => 'template',
          );
          
          if ( $wp_query->is_404 ) array_push($args['meta_query'], array('key' => 'targets', 'value' => '404', 'compare' => 'LIKE'));
          if ( $wp_query->is_archive || $wp_query->is_posts_page ) array_push($args['meta_query'], array('key' => 'targets', 'value' => "archive", 'compare' => 'LIKE'));
          if ( $wp_query->is_posts_page || $wp_query->is_category || $wp_query->is_tag ) array_push($args['meta_query'], array('key' => 'targets', 'value' => "posts", 'compare' => 'LIKE'));
          if ( $wp_query->is_single ) array_push($args['meta_query'], array('key' => 'targets', 'value' => ( isset($wp_query->query['post_type']) ? $wp_query->query['post_type'] : "posts"), 'compare' => 'LIKE'));
          if ( $wp_query->is_post_type_archive ) array_push($args['meta_query'], array('key' => 'targets', 'value' => "{$wp_query->query['post_type']}%archive", 'compare' => 'LIKE'));
          if ( $wp_query->is_page || (function_exists('is_woocommerce') && is_woocommerce() && !isset($wp_query->query['product']) && !$wp_query->is_archive) ) array_push($args['meta_query'], array('key' => 'targets', 'value' => 'pages', 'compare' => 'LIKE'));
          
          if ( empty($args['meta_query']) ) unset($args['meta_query']);
          $templates = new WP_Query($args);
          
          if ( $templates->have_posts() ) {
            global $post;
            
            $single = array('pages', 'posts', 'priority', 'products');
            
            foreach ($templates->posts as $key => $template) {
              $area = get_field('location', $template->ID);
              
              if ( $targets = get_field('targets', $template->ID) ) {
                if ( in_array('archive', $targets) ) {
                  if ( $archives = get_field('include_archives', $template->ID) ) {
                    if ( !is_post_type_archive($archives) ) continue;
                  }
                }
                
                if ( $target = array_intersect($targets, $single) ) {
                  $include = get_field('include', $template->ID);
                  $exclude = get_field('exclude', $template->ID);
                  
                  if ( is_array($exclude) && in_array($post->ID, $exclude)) continue;
                  if ( is_array($include) && !in_array($post->ID, $include)) continue;
                }
              }
              
              if ( isset($this->templates[$area]) ) array_push($this->templates[$area], $template);
            }
          }
          else $this->templates = false;
        }
      }
      public function action_wp_enqueue_scripts() {
        $template = get_page_template() ?: FALSE;
        
        if ( !$template || strpos($template, 'page-tool.php') < 0 ) {
          wp_enqueue_script('icons');
        }
        
        if ( strpos($template, 'page-manage.php') > 0 || strpos($template, 'page-matches.php') > 0 || strpos($template, 'page-playoffs.php') > 0 || is_singular('team')  ) {
          wp_enqueue_style('block-acf-dl-events');
        }
      }
      
    // Hooks : Filters
      public function filters() {
        add_filter('acf/fields/relationship/query/key=field_5faebcd5757cf', array($this, 'filter_acf_fields_relationship_query'), 20, 3);
        add_filter('acf/fields/relationship/query/key=field_5fb1e370c7545', array($this, 'filter_acf_fields_relationship_query'), 20, 3);
        add_filter('acf/load_value/key=field_60c6b9c8f832d', array($this, 'filter_acf_load_value_discord_username'), 20, 3);
        add_filter('acf/load_value/key=field_63a241dd6d4ca', array($this, 'filter_acf_load_value_gamer_id'), 20, 3);
        add_filter('lostpassword_errors', array($this, 'tml_wp_login_errors'));
        add_filter('pxl_template', array($this, 'filter_pxl_template'), 20, 2);
        add_filter('pxl_wrap', array($this, 'filter_pxl_wrap'), 10, 4);
        add_filter('registration_errors', array($this, 'tml_validate_form_fields'));
        add_filter('render_block_data', array($this, 'filter_render_block_data'), 10, 2);
        add_filter('tml_ajax_error_data', array($this, 'tml_ajax_error_data'));
        add_filter('wp_login_errors', array($this, 'tml_wp_login_errors'));
      }
      public function filter_acf_fields_relationship_query( $args, $field, $post_id ) {
        if ( strpos($_SERVER['HTTP_REFERER'], 'wp-admin') ) return $args;
        
        $players = new WP_User_Query(array(
          'fields'       => array('display_name'),
          'meta_key'     => 'dl_team',
          'meta_value'   => $post_id,
          'meta_type'    => 'NUMERIC',
          'meta_compare' => '=',
        ));
        
        $players = array_column($players->results, 'display_name');
        
        $args['meta_query'] = array(
          array(
            'key'     => 'discord_username',
            'value'   => $players,
            'compare' => 'IN'
          ),
        );
        
        return $args;
      }
      public function filter_acf_load_value_discord_username( $value, $post_id, $field ) {
        if ( isset($_REQUEST['discord_username']) ) {
          $value = $_REQUEST['discord_username'];
        }
        
        return $value;
      }
      public function filter_acf_load_value_gamer_id( $value, $post_id, $field ) {
        if ( isset($_REQUEST['gamer_id']) ) {
          $value = $_REQUEST['gamer_id'];
        }
        
        return $value;
      }
      public function filter_pxl_template( $pxl_template, $template ) {
        if ( strpos($template, 'betterdocs') ) return false;
        
        return $pxl_template;
      }
      public function filter_pxl_wrap( $wrap, $area, $template, $post ) {
        if ( strpos($template, 'tml.php') > 0 ) return FALSE;
        
        return $wrap;
      }
      public function filter_render_block_data( $block, $source_block ) {
        if ( $this->template ) {
          if ( $this->template === 'pre-content' ) {
            global $post_parent;
            
            if ( is_page() ) {
              global $post;
              $image = pxl::image($post_parent, array('w' => 1600, 'return' => 'url'));
            
              if ( $image && $block['blockName'] === 'core/cover' ) {
                $block['innerContent'][0] = str_replace($block['attrs']['url'], $image, $block['innerContent'][0]);
              }
              else if ( $block['blockName'] === 'core/heading' || $block['blockName'] === 'core/paragraph' ) {
                $block['innerContent'][0] = str_replace('Title', get_the_title($post_parent), $block['innerContent'][0]);
              }
            }
            else {
              $opts = get_field('cpt_archive', 'options');
              
              if ( $opts && $opts['banner_image'] && $block['blockName'] === 'core/cover' ) {
                $image = pxl::image($opts['banner_image'], array('w' => 1600, 'return' => 'url'));
                $block['innerContent'][0] = str_replace($block['attrs']['url'], $image, $block['innerContent'][0]);
              }
              else if ( $block['blockName'] === 'core/heading' || $block['blockName'] === 'core/paragraph' ) {
                if ( is_category() || is_tag() || is_tax() ) $title = is_post_type_archive() ? post_type_archive_title('', false) : single_cat_title('', false);
                else if ( is_search() ) $title = 'Search: ' . get_search_query();
                else if ( is_404() ) $title = '404 – Page not Found';
                else $title = empty($opts['title']) ? ( is_singular('priority') ? 'Priorities' : get_the_title(get_option('page_for_posts'))) : $opts['title'];
                
                $block['innerContent'][0] = str_replace('Title', $title, $block['innerContent'][0]);
              }
            }
          }
        }
        
        return $block;
      }
      
    // Functions : Theme
      public function access( $roles ) {
        $access = FALSE;
        $roles  = explode('|', $roles);
        
        if ( is_user_logged_in() ) {
          $user = wp_get_current_user();
          if ( array_intersect( $roles, $user->roles ) ) $access = TRUE;
        }
        
        return $access;
      }
      public function template( $area, $wrap = FALSE ) {
        if ( $this->templates && !empty($this->templates[$area]) ) {
          $this->template = $area;
            global $post, $post_parent; $post_parent = $post;
            
            if ( $area === 'modal' ) {
              wp_enqueue_style('magnific-popup');
              wp_enqueue_script('modal.min');
              include(PARTIAL . 'modal.php');
            }
            else {
              if ( $wrap ) printf('<div id="template-%s" class="content">', $area);
              foreach ($this->templates[$area] as $index => $post) {
                setup_postdata($post);
                the_content();
              }
              if ( $wrap ) echo '</div>';
            }
            
            wp_reset_postdata();
            if ( is_404() ) $post = null;
          $this->template = false;
        }
      }
      public function season_dates() {
        $cycle  = FALSE;
        $date   = date('m/d/Y');
        $season = array(
          'label' => 'New Season',
          'value' => 'Coming Soon'
        );
        
        if ( $dates = get_field('season_dates', 'options', TRUE) ) {
          $now   = date_create($date);
          $start = date_create($dates['regular_start']);
          $end   = date_create($dates['regular_end']);
          
          if ( $now <= $start ) {
            $cycle = 1;
            $season['value'] = $dates['regular_start'];
          }
          else if ( $now >= $start && $now <= $end ) {
            $diff    = date_diff($now, $start);
            $week    = floor($diff->days / 7);
            $weeks   = array('one', 'one', 'two', 'two', 'three', 'three', 'three', 'four', 'four', 'five', 'five', 'six', 'six');
            $numbers = array('zero' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'six' => 6);
            
            $season['label'] = 'Cycle';
            $season['value'] = isset($weeks[$week]) ? $weeks[$week] : 'six';
            
            $cycle = isset($weeks[$week]) ? $numbers[$weeks[$week]] : FALSE;
          }
          else if ( $date > $end && $date < $dates['quarterfinals_day_one'] ) {
            $diff = date_diff(date_create($date), date_create($dates['quarterfinals_day_one']));
            
            $season['label'] = 'Playoffs Start';
            $season['value'] = "{$diff->days} days!";
          }
          else if ( $date >= $dates['quarterfinals_day_one'] && $date <= $dates['quarterfinals_day_two'] ) {
            $season['label'] = 'Playoffs';
            $season['value'] = 'Quarterfinals Today!';
          }
          else if ( $date > $dates['quarterfinals_day_two'] && $date < $dates['semifinals'] ) {
            $diff = date_diff(date_create($date), date_create($dates['semifinals']));
            
            $season['label'] = 'Semifinals Start';
            $season['value'] = "{$diff->days} days!";
          }
          else if ( $date == $dates['semifinals'] ) {
            $season['label'] = 'Playoffs';
            $season['value'] = 'Semifinals Today!';
          }
          else if ( $date > $dates['semifinals'] && $date < $dates['finals'] ) {
            $diff = date_diff(date_create($date), date_create($dates['finals']));
            
            $season['label'] = 'Finals Start';
            $season['value'] = "{$diff->days} days!";
          }
          else if ( $date == $dates['finals'] ) {
            $season['label'] = 'Playoffs';
            $season['value'] = 'Finals Today!';
          }
          else if ( $date > $dates['finals'] && $date < $dates['all-stars_games_awards_ceremony_day_one'] ) {
            $diff = date_diff(date_create($date), date_create($dates['all-stars_games_awards_ceremony_day_one']));
            
            $season['label'] = 'All Stars Start';
            $season['value'] = "{$diff->days} days!";
          }
          else if ( $date <= $dates['all-stars_games_awards_ceremony_day_two'] ) {
            $season['label'] = 'Awards Ceremonies &';
            $season['value'] = 'All Stars';
          }
        }
        
        $this->cycle        = $cycle ? $cycle : 6;
        $this->season_dates = $dates;
        
        if ( isset($dates['new_player_cut-off']) && !empty($dates['new_player_cut-off']) ) {
          $date = new datetime('now');
          $date->setTimeZone(new DateTimeZone('America/Los_Angeles'));
          
          $lock = DateTime::createFromFormat("m/d/Y\TH:i:sT", "{$dates['new_player_cut-off']}T23:59:59 -08:00");
          $end  = DateTime::createFromFormat("m/d/Y", $this->season['playoffs_end']);
          $this->season_dates['locked'] = $date->format('Ymd') > $lock->format('Ymd') && $date->format('Ymd H:m') <= $end->format('Ymd 12:00');
          
          $start = DateTime::createFromFormat("m/d/Y", $dates['regular_start']);
          $this->season_dates['locked_names'] = $date->format('Ymd') >= $start->format('Ymd') && $date->format('Ymd H:m') <= $end->format('Ymd 12:00');
        }
        else {
          $this->season_dates['locked'] = FALSE;
          $this->season_dates['locked_names'] = FALSE;
        }
        
        return $season;
      }
      private function dl_team_options( $user = FALSE ) {
        $options = array();
        $team    = $user ? get_the_author_meta('dl_team', $user->ID) : 'Free Agent';
        $teams   = array_column(get_posts(array('post_type' => 'team', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC')), 'post_title', 'ID');
        
        foreach ($teams as $id => $title) {
          $selected = $team && $id == $team ? ' selected="selected"' : '';
          array_push($options, sprintf('<option value="%s"%s>%s</option>', $id, $selected, $title));
        }
        
        $options = implode("\n", $options);
        
        $options = sprintf('<option value="Free Agent"%s>Free Agent (no team)</option>', ($team == 'Free Agent' ? ' selected' : '')) . $options;
        $options = sprintf('<option value="Inactive"%s>Inactive</option>', ($team == 'Inactive' ? ' selected' : '')) . $options;
        
        return $options;
      }
      
    // Functions : TML
      public function tml_add_form_field() {
        if ( function_exists('tml_add_form_field') ) {
          $this->season_dates();
          
          $teams = array('Free Agent' => 'Free Agent (no team)') + array_column(get_posts(array(
            'post_type'      => 'team',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
          )), 'post_title', 'ID');
          
          $user       = wp_get_current_user();
          
          $server     = $user ? get_the_author_meta('dl_idealserver', $user->ID) : FALSE;
          $servers    = fns::array_to_attrs($this->servers, 'option', $server);
          $s_history  = get_the_author_meta('dl_idealservers', $user->ID);
          
          if ( $s_history ) {
            $current = array_slice($s_history, -1);
            
            if ( $current[0] == $server ) array_pop($s_history);
            
            $s_history = !empty($s_history) ? sprintf('<span class="tml-description"><small>Historical Record<ol><li>%s</li></ol></small></span>', implode('</li><li>', $s_history)) : FALSE;
          }
          
          $timezone   = $user ? get_the_author_meta('dl_timezone', $user->ID) : '';
          $timezones  = wp_timezone_choice($timezone);
          $tz_history = get_the_author_meta('dl_timezones', $user->ID);
          
          if ( $tz_history ) {
            $current = array_slice($tz_history, -1);
            
            if ( $current[0] == $timezone ) array_pop($tz_history);
            
            $tz_history = !empty($tz_history) ? sprintf('<span class="tml-description"><small>Historical Record<ol><li>%s</li></ol></small></span>', implode('</li><li>', $tz_history)) : FALSE;
          }
          
          $locks = [];
          
          if ( $this->season_dates['locked_names'] ) {
            $locks[] = 'GAMERTAG';
            $locks[] = 'GAMER ID';
            $locks[] = 'GAMER ID ALT';
          }
          
          if ( $this->season_dates['locked'] ) {
            $locks[] = 'TEAM';
          }
          
          $form_fields = array(
            'profile' => array(
              'hr1' => array( 'priority' => 1, 'type' => 'custom', 'render_args' => array('after' => '',
                'before' => sprintf(
                  '
                    %s
                    <strong><big>Gamer Info</big></strong><hr class="hr hr--thin"/>
                  ',
                  !empty($locks) ? sprintf('<div class="notice notice--red">The following fields are now locked for the season:<ul><li>%s</li></ul></div>', implode('</li><li>', $locks)) : ''
                ))
              ),
                'dl_team'      => array( 'priority' => 2, 'label' => 'Team', 'type' => 'dropdown', 'options' => $teams, 'description' => (!$this->season_dates['locked'] ? 'Changing this will de-roster you from your current team.' : '')),
                'nickname'     => array( 'priority' => 3, 'label' => 'Gamertag', 'attributes' => []),
                'gamer_id'     => array( 'priority' => 4, 'label' => __('Headset - Primary'), 'attributes' => [], 'render_args' => array('before' => '<div class="tml-field-wrap tml-%s-wrap">Gamer ID(s) <small><a href="/how-to-find-your-gamer-id/" target="_blank">[How do I find my Gamer ID?]</a></small>'), 'attributes' => ['placeholder' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX', 'minlength' => 5]),
                'gamer_id_alt' => array( 'priority' => 5, 'label' => __('Headset - Secondary'), 'attributes' => ['placeholder' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX']),
              'hr2' => array( 'priority' => 10, 'type' => 'custom', 'render_args' => array('after' => '',
                'before' => '<hr class="hr hr--spacer"/><strong><big>Server & Timezone</big></strong><hr class="hr hr--thin"/>'
              )),
                'dl_idealserver' => array( 'priority' => 11, 'label' => 'Lowest Ping Server', 'type' => 'custom', 'content' => sprintf('<select name="dl_idealserver" id="dl_idealserver" class="tml-field">%s</select>', $servers), 'render_args' => array(
                  'before' => '<div class="tml-field-wrap tml-dl_idealserver-wrap">',
                  'after' => "{$s_history}</div>"
                )),
                'dl_timezone'     => array( 'priority' => 12, 'label' => 'Timezone', 'type' => 'custom', 'content' => sprintf('<select name="dl_timezone" id="dl_timezone" class="tml-field">%s</select>', $timezones), 'render_args' => array(
                  'before' => '<div class="tml-field-wrap tml-dl_timezone-wrap">',
                  'after' => "{$tz_history}</div>"
                )),
                
              'hr3' => array( 'priority' => 20, 'type' => 'custom', 'render_args' => array('after' => '',
                'before' => '<hr class="hr hr--spacer"/><strong><big>User Info</big></strong><hr class="hr hr--thin"/>'
              )),
                'discord'     => array( 'priority' => 21, 'label' => 'Discord Username', 'description' => 'If you need this changed please contact a moderator.', 'attributes' => array('disabled' => TRUE)),
            ),
            'register' => array(
              'hr1' => array( 'priority' => 1, 'type' => 'custom', 'render_args' => array('after' => '', 'before' => '<strong><big>Gamer Info</big></strong><hr class="hr hr--thin"/>')),
                'dl_team'      => array( 'priority' => 2, 'label' => __('Team'), 'type' => 'dropdown', 'options' => ( $this->season_dates['locked'] ? array('Free Agent' => 'Free Agent (roster locked)') : $teams)),
                'nickname'     => array( 'priority' => 3, 'label' => __('Gamertag')),
                'gamer_id'     => array( 'priority' => 4, 'label' => __('Headset - Primary'), 'attributes' => [], 'render_args' => array('before' => '<div class="tml-field-wrap tml-%s-wrap">Gamer ID(s) <small><a href="/how-to-find-your-gamer-id/" target="_blank">[How do I find my Gamer ID?]</a></small>'), 'attributes' => ['placeholder' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX', 'minlength' => 5]),
                'gamer_id_alt' => array( 'priority' => 5, 'label' => __('Headset - Secondary'), 'attributes' => ['placeholder' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX']),
                
              'hr2' => array( 'priority' => 10, 'type' => 'custom', 'render_args' => array('after' => '', 'before' => '<hr class="hr hr--spacer"/><strong><big>Server & Timezone</big></strong><hr class="hr hr--thin"/>')),
                'dl_idealserver' => array( 'priority' => 11, 'label' => 'Lowest Ping Server', 'type' => 'custom', 'content' => sprintf('<select name="dl_idealserver" id="dl_idealserver" class="tml-field">%s</select>', $servers)),
                'dl_timezone'    => array( 'priority' => 12, 'label' => 'Timezone', 'type' => 'custom', 'content' => sprintf('<select name="dl_timezone" id="dl_timezone" class="tml-field">%s</select>', $timezones)),
                
              'hr3' => array( 'priority' => 15, 'type' => 'custom', 'render_args' => array('after' => '', 'before' => '<hr class="hr hr--spacer"/><strong><big>User Info</big></strong><hr class="hr hr--thin"/>')),
                'discord'    => array( 'priority' => 16, 'label' => __('Discord'), 'description' => ''),
                'user_email' => array( 'priority' => 17, 'label' => __('Email'), 'type' => 'email', 'id' => 'user_email', 'attributes' => array('maxlength' => 200)),
                'user_login'   => array( 'priority' => 18, 'label' => __('Username'), 'description' => ''),
            )
          );
          
          if ( $this->season_dates['locked'] ) {
            if ( !isset($form_fields['profile']['dl_team']['attributes']) ) $form_fields['profile']['dl_team']['attributes'] = [];
            $form_fields['profile']['dl_team']['attributes'] = array_merge($form_fields['profile']['dl_team']['attributes'], ['disabled' => TRUE]);
          }
          
          if ( $this->season_dates['locked_names'] ) {
            $form_fields['profile']['nickname']['attributes']     = array_merge($form_fields['profile']['nickname']['attributes'], ['readonly' => TRUE]);
            $form_fields['profile']['gamer_id']['attributes']     = array_merge($form_fields['profile']['gamer_id']['attributes'], ['readonly' => TRUE]);
            $form_fields['profile']['gamer_id_alt']['attributes'] = array_merge($form_fields['profile']['gamer_id_alt']['attributes'], ['readonly' => TRUE]);
          }
          
          $user    = wp_get_current_user();
          $default = array('type' => 'text');
          
          foreach ($form_fields as $form => $fields) {
            foreach ($fields as $slug => $args) {
              $args = array_merge($default, $args);
              
              $args['id']    = $slug;
              $args['value'] = tml_get_request_value($slug, 'any');
              
              if ( $user ) $args['value'] = get_user_meta( $user->ID, $slug, true );
              
              tml_add_form_field($form, $slug, $args);
            }
          }
        }
      }
      public function tml_wp_login_errors( $errors ) {
        $this->tml_errors = $errors;
        
        return $errors;
      }
      public function tml_remove_form_field() {
        if ( function_exists('tml_remove_form_field') ) {
          // tml_remove_form_field('register', 'user_email');
          
          tml_remove_form_field('profile', 'personal_options_section_header');
          tml_remove_form_field('profile', 'admin_bar_front');
          tml_remove_form_field('profile', 'name_section_header');
          
          tml_remove_form_field('profile', 'first_name');
          tml_remove_form_field('profile', 'last_name');
          
          tml_remove_form_field('profile', 'pass1');
          tml_remove_form_field('profile', 'pass2');
          
          // tml_remove_form_field('profile', 'nickname'); // want to hide but need to fiure out around the requirement to have it
          tml_remove_form_field('profile', 'display_name');
          tml_remove_form_field('profile', 'contact_info_section_header');
          tml_remove_form_field('profile', 'about_yourself_section_header');
          tml_remove_form_field('profile', 'description');
          tml_remove_form_field('profile', 'avatar');
          tml_remove_form_field('profile', 'account_management_section_header');
          tml_remove_form_field('profile', 'user_login');
          tml_remove_form_field('profile', 'url');
        }
      }
      public function tml_registration_save_form_fields( $user_id ) {
        if ( isset($_POST['discord']) ) {
          $name = $_POST['discord'];
          update_user_meta($user_id, 'discord', $name);
          wp_update_user(array('ID' => $user_id, 'display_name' => $name));
        }
        
        if ( isset($_POST['nickname']) ) {
          wp_update_user(array('ID' => $user_id, 'first_name' => $_POST['nickname'], 'user_nicename' => $_POST['nickname']));
          
          $player = new WP_Query([ 'posts_per_page' => 1, 'post_type' => 'player', 'title' => $_POST['nickname'] ]);
          $player = $player->post;
          
          if ( !$player ) {
            $season = get_term_by('slug', 'current', 'season');
            
            $player_id = wp_insert_post(array(
              'post_title'  => $_POST['nickname'],
              'post_type'   => 'player',
              'post_status' => 'publish',
              'meta_input'  => array(
                'discord_username'  => $_POST['discord'],
                '_discord_username' => 'field_60c6b9c8f832d',
                'gamer_id'          => $_POST['gamer_id'],
                '_gamer_id'         => 'field_63a241dd6d4ca',
                'gamer_id_alt'      => $_POST['gamer_id_alt'],
                '_gamer_id_alt'     => 'field_63c22257d2e4d',
              ),
              'tax_input' => array(
                'season' => $season->term_id
              )
            ));
          }
        }
        
        if ( isset($_POST['gamer_id']) ) update_user_meta($user_id, 'gamer_id', sanitize_text_field($_POST['gamer_id']));
        
        if ( isset($_POST['gamer_id_alt']) ) update_user_meta($user_id, 'gamer_id_alt', sanitize_text_field($_POST['gamer_id_alt']));
        
        if ( isset($_POST['dl_team']) ) update_user_meta($user_id, 'dl_team', sanitize_text_field($_POST['dl_team']));
        
        if ( isset($_POST['dl_idealserver']) ) update_user_meta($user_id, 'dl_idealserver', sanitize_text_field($_POST['dl_idealserver']));
        
        if ( isset($_POST['dl_timezone']) ) update_user_meta($user_id, 'dl_timezone', sanitize_text_field($_POST['dl_timezone']));
      }
      public function tml_validate_form_fields( $errors ) {
        $this->tml_errors = $errors;
        
        if ( empty($_POST['discord']) ) $errors->add('discord', '<strong>Error</strong>: Please enter your discord username.');
        
        if ( empty($_POST['gamer_id']) ) $errors->add('gamer_id', '<strong>Error</strong>: Please enter your gamer id.');
        
        if ( empty($_POST['user_email']) ) $errors->add('user_email', '<strong>Error</strong>: Please enter your email.');
        
        if ( empty($_POST['user_login']) ) $errors->add('user_login', '<strong>Error</strong>: Please enter a username.');
        
        if ( isset($_POST['nickname']) ) {
          $player  = new WP_Query([ 'posts_per_page' => 1, 'post_type' => 'player', 'title' => $_POST['nickname'] ]);
          $player  = $player->post;
          $discord = get_field('discord_username', $player->ID);
          
          if ( $player ) {
            if ( $discord && $discord !== $_POST['discord'] ) $errors->add('nickname', '<strong>Error</strong>: The Discord Username you are trying to register does not match the player profile. Contact Styledev for help.');
            else {
              global $wpdb;
              $sql = $wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key = 'discord' AND meta_value = '%s'", $discord);
              $user = $wpdb->get_var($sql);
              if ( $user ) $errors->add('nickname', '<strong>Error</strong>: This gamertag is aleady in use by another player. Please choose another one.');
            }
          }
        }
        
        $servers = tml_get_request_value('dl_idealserver', 'post');
        if ( ! $servers || $servers == '' ) {
          $errors->add('dl_idealserver', '<strong>ERROR</strong>: You must pick a server.');
        }
        
        $timezone = tml_get_request_value('dl_timezone', 'post');
        if ( ! $timezone || $timezone == '' ) {
          $errors->add('dl_timezone', '<strong>ERROR</strong>: You must pick a timezone.');
        }
        
        return $errors;
      }
      public function tml_ajax_error_data( $data ) {
        if ( $this->tml_errors ) {
          return array(
            'fields' => $this->tml_errors->get_error_codes(),
            'errors' => $data['errors']
          );
        }
        
        return $data;
      }
  }
}
