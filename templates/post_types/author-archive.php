<div class="content">
  <?php
    $player = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
    
    printf('<h2>%s</h2>', $player->data->display_name);
    fns::put($player->ID);
  ?>
</div>