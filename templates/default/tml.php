<?php
  $action = get_query_var('action');
  
  if ( $action === 'register' ) :
    wp_enqueue_script('register');
?>
    <style>
      form[name="register"]{margin-bottom:1em;}
      .tml-register .tml-alerts .tml-error{margin-top:1em;}
      .tml .tml-field-wrap{margin:0;}
      .tml * + .tml-field-wrap{margin-top:1em;}
      .tml-indicator-wrap{position:relative;}
      .tml-indicator_hint-wrap{font-size:smaller;}
      #pass-strength-result{border-bottom-right-radius:4px;border-top-right-radius:4px;margin:0;padding:0 .25em;padding:5px 12px;position:absolute;right:0;top:-137px;}
      #pass-strength-result:not(.bad):not(.short):not(.good):not(.strong){border:0;height:0;margin:0;padding:0;}
      @media( max-width:780px ){#pass-strength-result{top:-101px;}}
      @media( max-width:480px ){#pass-strength-result{top:-97px;}}
    </style>
<?php endif; ?>
<style>
  h1{margin:0;}
  .utility{background-color:var(--dark);background-image:url('/wp-content/themes/dashleague-v1/resources/images/quarry.jpg');background-position:center;background-size:cover;display:flex;min-height:100vh;padding:20px 20px 40px;position:relative;}
  .utility:before{background-color:rgba(0,0,0,0.5);bottom:0;content:'';left:0;position:absolute;right:0;top:0;}
  .utility__inner{margin:auto;width:600px;max-width:100%;position:relative;z-index:10;}
  .utility__wordmark{color:#fff;display:block;margin:20px 0;text-align:right;}
  .utility__wordmark svg g{fill:#fff;}
  .utility-box{background-color:#fff;border-radius:6px;display:flex;overflow:hidden;}
  .utility-box__desc{background-color:var(--red);flex:1;padding:30px;display:flex;flex-direction:column;}
  .utility-box__title{color:#fff;margin-top:0;flex:1;}
  .utility-box__content{padding:30px;flex:3;}
  .utility__logomark{opacity:0.2;position:fixed;bottom:20px;right:20px;}
  .utility__buttons{display:flex;justify-content:space-between;}
  .utility__buttons .tml-field-wrap{margin-bottom:0;}
  .utility__buttons .tml-submit-wrap{order:3;}
  .utility__buttons .tml-submit-wrap button{background-color:var(--blue);}
  .utility__buttons .tml-submit-wrap button:hover{background-color:var(--blue-dark);}
  .error{background-color:#ffdddd;border-color:#ff2929;}
  .tml-button{padding:0.5em;width:100%;}
  .tml-label[for="rememberme"]{padding:0;vertical-align:middle;}
  
  @media(max-width:480px) {
    .utility__wordmark{text-align:center;}
    .utility-box{flex-direction:column;}
    .utility-box__desc{padding:10px 30px;}
    .utility-box__desc h1{display:none;}
    .tml-field-wrap button{width:100%;}
    .tml-links{text-align:center;}
  }
</style>

<div class="utility">
  <div class="utility__inner">
    <a href="/" class="utility__wordmark">
      <?php
        $logo = RESOURCE . '/images/logo-flat.svg';
        if ( file_exists($logo) ) include($logo);
      ?>
    </a>
    <div class="utility-box">
      <div class="utility-box__desc">
        <h1 class="h3 utility-box__title"><?php the_title() ?></h1>
      </div>
      <div class="utility-box__content">
        <?php the_content() ?>
      </div>
    </div>
  </div>
  <script>
    jQuery(document).ajaxSend(function(event, xhr, settings) {
      var errors = document.querySelectorAll('.error');
      errors.forEach(function(el) { el.classList.remove('error'); });
    });
    
    jQuery(document).ajaxComplete(function(e, xhr, settings) {
      var container = jQuery(e.delegateTarget),
          notices   = container.find('.tml-alerts'),
          response  = xhr.responseJSON;
          
      e.preventDefault();
      
      notices.empty();
      
      if ( response.success ) {
        if ( response.data.refresh ) location.reload(true);
        else if ( response.data.redirect ) location.href = response.data.redirect;
        else if ( response.data.notice ) notices.hide().html( response.data.notice ).fadeIn();
      }
      else {
        response.data.fields.forEach(function(field) {
          el = false;
          
          switch (field) {
            case 'email_exists':
            case 'invalid_email':     el = document.querySelector('#user_email'); break;
            case 'invalid_username':  el = document.querySelector('#user_login'); break;
            case 'password_mismatch': el = document.querySelector('#pass2'); break;
            case 'username_exists':   el = document.querySelector('#user_login'); break;
            default: el = document.querySelector('#'+field);
          }
          
          if ( el ) el.classList.add('error');
        });
        
        notices.hide().html( response.data.errors ).fadeIn();
        
        if ( 'undefined' !== typeof register ) {
          if ( register.el.button ) register.el.button.classList.remove('button--sending');
          if ( register.el.$ ) register.el.$.classList.remove('form--sending');
        }
      }
    });
  </script>
</div>