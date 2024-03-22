<?php if ( !class_exists('themeConfig') ) {
  class config {
    private $options;
    function __construct() {
      global $pxlf;
      
      $pxlf->custom['plugins'] = array(
        array(
          'name' => 'Advanced Custom Fields PRO', 'slug' => 'advanced-custom-fields-pro', 'source' => 'pxlup', 'external_url' => 'http://advancedcustomfields.com/',
          'force_activation' => FALSE,
          'required'         => FALSE,
        ),
        array(
          'name' => 'Gravity Forms', 'slug' => 'gravityforms', 'source' => 'pxlup', 'external_url' => 'http://www.gravityforms.com/',
          'force_activation' => FALSE,
          'required'         => FALSE,
        ),
        array(
          'name' => 'Stream', 'slug' => 'stream',
          'force_activation' => FALSE,
          'required'         => FALSE,
        ),
      );
      
      $this->actions();
      $this->filters();
    }
    
    // Hooks : Actions
      public function actions() {
        add_action('init', array($this, 'action_init'));
        add_action('gform_enqueue_scripts', array($this, 'action_gform_enqueue_scripts'), 10);
        add_action('wp_body_open', array($this, 'action_wp_body_open'));
        add_action('wp_footer', array($this, 'action_wp_footer'), 10, 1);
        add_action('wp_head', array($this, 'action_wp_head'), 100);
      }
      public function action_init() {
        if ( is_admin() ) $this->acf_options();
        
        // add_rewrite_rule('discord/?$', 'index.php?action=register_discord', 'top');
      }
      public function action_gform_enqueue_scripts() {
        wp_enqueue_style('gf');
        // gravity_form_enqueue_scripts(1, TRUE);
      }
      public function action_wp_body_open() {
        echo get_field('scripts_body_start', 'options');
      }
      public function action_wp_head() {
        echo '
          <link rel="preconnect" href="https://fonts.gstatic.com">
          <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200;400;600&family=Yantramanav:wght@500;700&display=swap" rel="stylesheet">
        ';
        
        $this->css_inline('critical.css');
        
        global $post_type;
        $this->css_inline("{$post_type}.css");
        
        $this->css_inline(str_replace(
          array('templates/', '.php'),
          array('', '.css'),
          get_page_template_slug()
        ));
        
        $template = get_page_template();
        if ( $template && strpos($template, 'page-account') > 0 ) acf_form_head();
        
        echo get_field('scripts_head', 'options');
      }
      public function action_wp_footer() {
        echo get_field('scripts_body_end', 'options');
      }
      
    // Hooks : Filters
      public function filters() {
        add_filter('gform_confirmation_anchor', '__return_false');
        add_filter('oembed_response_data', array($this, 'filter_oembed_response_data'), 20);
      }
      public function filter_oembed_response_data( $data ) {
        unset($data['author_name']);
        unset($data['author_url']);
        return $data;
      }
      
    // Public: Helpers
      public function css_inline( $file ) {
        if ( empty($file) || $file == '.css' ) return;
        
        $file = locate_template(array(
          "resources/css/$file",
          "resources/css/$file.php"
        ));
        
        if ( file_exists($file) ) {
          ob_start();
            include($file);
          $css = $this->css_minify(ob_get_clean());
          printf('<style>%s</style>', $css);
        }
      }
      
    // Private: Theme Setup
      private function acf_options() {
        if ( function_exists('acf_add_options_page') ) {
          global $pxlf;
          
          $this->options = acf_add_options_page(array(
            'capability' => 'manage_options',
            'menu_title' => 'Theme',
            'page_title' => 'Theme',
            'redirect'   => TRUE,
            'position'   => "50.1",
            'icon_url'   => 'dashicons-welcome-widgets-menus',
          ));
          
          acf_add_options_sub_page(array(
            'capability'  => 'manage_options',
            'menu_title'  => 'General',
            'page_title'  => 'General',
            'parent_slug' => $this->options['menu_slug'],
          ));
          
          acf_add_options_sub_page(array(
            'capability'  => 'manage_options',
            'menu_title'  => 'Scripts',
            'page_title'  => 'Scripts',
            'parent_slug' => $this->options['menu_slug'],
          ));
          
          acf_add_options_sub_page(array(
            'capability'  => 'manage_options',
            'menu_title'  => 'Post Options',
            'page_title'  => 'Post Options',
            'parent_slug' => '/edit.php',
          ));
          
          if ( !empty($pxlf->post_types) ) {
            foreach ($pxlf->post_types as $slug => $args) {
              if ( isset($args['options']) && $args['options'] === FALSE ) {
                unset($pxlf->post_types[$slug]['options']);
                if ( empty($pxlf->post_types[$slug]) ) $pxlf->post_types[$slug] = TRUE;
                continue;
              }
              
              $title = isset($args['labels']) ? $args['labels']['singular_name'] : ucwords(str_replace(array('-', '_'), array(' ', ' '), $slug));
              $slug  = str_replace('-', '_', $slug);
              
              acf_add_options_sub_page(array(
                'capability'  => 'manage_options',
                'menu_title'  => "$title Options",
                'page_title'  => "$title Options",
                'parent_slug' => "/edit.php?post_type=$slug",
              ));
            }
          }
        }
      }
      private function css_minify( $css ){
        $css = preg_replace('/\/\*((?!\*\/).)*\*\//','',$css); // negative look ahead
        $css = preg_replace('/\s{2,}/',' ',$css);
        $css = preg_replace('/\s*([:;{}])\s*/','$1',$css);
        $css = preg_replace('/;}/','}',$css);
        return $css;
      }
  }
}
