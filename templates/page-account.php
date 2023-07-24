<?php /* Template Name: Account */
  if ( $pxl->access('administrator|captain|player|subscriber') ) :
    
    wp_enqueue_script('api-account');
    
    $user = new dlUser();
?>
<style>
  .content{padding-bottom:2em;}
  .tml-button[type="button"]{display:none;}
  .team{margin-bottom:1em;}
</style>
<div class="content">
  <div class="">
    <div class="page-heading">
      <h1 class="h2">Account</h1>
    </div>
    <div class="subnav">
      <?php
        $parent_id = $post->post_parent == 0 ? $post->ID : $post->post_parent;
        $parent    = get_post($parent_id);
        $pages     = get_posts( array(
          'post_type'      => 'page',
          'posts_per_page' => -1,
          'post_status'    => 'published',
          'post_parent'    => $parent_id,
          'order'          => 'ASC',
          'orderby'        => 'menu_order',
        ));
        
        $pclass = $post->ID == $parent_id ? 'subnav__link--current' : '';
      ?>
      
      <a class="subnav__link <?php echo $pclass ?>" href="<?php echo get_permalink($parent_id) ?>"><?php echo $parent->post_title ?></a>
      
      <?php
        foreach($pages as $page) {
          $roles = get_post_meta($page->ID, '_members_access_role', false);
          
          if ( !empty($roles) ) {
            $pass = FALSE;
            foreach ($roles as $role) if ( current_user_can($role) ) $pass = TRUE;
            if ( !$pass ) continue;
          }
          
          $class = $post->ID == $page->ID ? ' subnav__link--current' : '';
          printf('<a class="subnav__link%s" href="%s">%s</a>', $class, get_permalink($page->ID), $page->post_title);
        }
        
        if ( $user->profile ) printf('<a class="subnav__link" href="%s">%s</a>', get_permalink($user->profile['ID']), 'Profile');
      ?>
    </div>
  </div>
  <?php
    global $post;
    
    if ( !$user->profile ) {
      echo '<br><div class="notice notice--red center-text"><small>Your account is not linked to your Player profile. Please DM Styledev with your Discord Username so he can link you account. If your account isnot linked to your Player profile you will not be able to edit the name that shows on your Player page.</small></div>';
    }
    
    the_content();
    
    $file = PARTIAL . "account-{$post->post_name}.php";
    
    if ( file_exists($file) ) include($file);
    
    if ( $post->post_name == 'account' ) :
  ?>
      <div class="">
        <hr class="hr">
        <a href="<?php echo wp_logout_url() ?>" class="btn">Logout <i class="far fa-sign-out"></i></a>
      </div>
  <?php endif; ?>
</div>
<?php endif; ?>