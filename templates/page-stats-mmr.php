<?php /* Template Name: Stats MMR */ ?>

<div class="content">
  <div class="alignwide">
    <?php
      if ( is_user_logged_in() && current_user_can('manage_options') ) {
        global $wpdb;
        
        $tool = isset($_GET['tool']) ? $_GET['tool'] : 'list';
        $file = PARTIAL . "mmr-{$tool}.php";
        if ( file_exists($file) ) include($file);
      }
    ?>
  </div>
</div>