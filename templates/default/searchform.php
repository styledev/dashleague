<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ) ?>">
  <label class="screen-reader-text" for="s"><?php echo _x( 'Search for:', 'label' ) ?></label>
  <input class="search-form__input" type="text" value="<?php echo get_search_query(); ?>" name="s" id="s">
  <button type="submit" class="search-form__submit"><i class="far fa-search fa-fw"></i></button>
</form>