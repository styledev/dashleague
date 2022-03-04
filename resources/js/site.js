(function($){
    
  // Wraps youtube videos with a responsive container
    function embedContainer() {
      jQuery('iframe[src*="youtube"]').each(function() {
        var $video = $(this);
        if ( ! $video.parent().hasClass('embed-container') ) $video.wrap("<div class='embed-container'></div>");
      });
    }
    
  // Adds a class to navbar when user is no longer at the top of the page for targeting style changes for fixed navbars
    var c, currentScrollTop = 0;
    function nav() {
      var $navbar = $('.navbar'),
          pos     = (document.documentElement.scrollTop||document.body.scrollTop);
          
      currentScrollTop = pos;
      
      if (c < currentScrollTop && pos > 100) $navbar.addClass("scrollUp");
      else if (c > currentScrollTop && !(pos <= 100)) $navbar.removeClass("scrollUp");
      
      if (pos > 10) $navbar.addClass("navbar--scrolled");
      else $navbar.removeClass("navbar--scrolled");
      
      c = currentScrollTop;
      
      return false;
    }
    
  // Menu Tray
    function menutray() {
      var body = document.querySelector('body'), menu = document.getElementById('menutray');
      
      if ( menu ) {
        var openBtns  = document.querySelectorAll('.more'),
            // closeBtn  = menu.querySelector('.closebtn'),
            activeBtn = false;
            
        menu.style.display = '';
        
        // closeBtn.addEventListener('click', function(e) {
        //   e.preventDefault();
        //   menu.classList.remove('open');
        //   removeClickListener();
        //   return false;
        // });
        
        const outsideClickListener = function( event ) {
          if ( !menu.contains(event.target) && !activeBtn.contains(event.target) ) {
            event.preventDefault();
            removeClickListener();
          }
        },
        removeClickListener = function() {
          body.classList.remove('scroll-lock');
          menu.classList.remove('open');
          document.removeEventListener('click', outsideClickListener, {capture:true})
        }
        
        openBtns.forEach(function( btn ) {
          btn.addEventListener('click', function( event ) {
            event.preventDefault();
            activeBtn = btn;
            
            if ( event.target == btn && menu.classList.contains('open') ) {
              menu.classList.remove('open');
              removeClickListener();
            }
            else {
              body.classList.add('scroll-lock');
              menu.classList.add('open');
              document.addEventListener('click', outsideClickListener, {capture:true});
            }
            
            return false;
          });
        });
      }
    }
    
  $(function(){
    embedContainer();
    nav();
    menutray();
    
    $(window).resize(function() {
      //if ( $('#navbar').hasClass('open') ) menuToggle();
    });
    
    $(window).scroll(function() { 
      nav(); 
    });
  })
})(jQuery);
