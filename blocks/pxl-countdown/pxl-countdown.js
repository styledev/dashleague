/* exported pxlCountdown */

class pxlCountdown {
  constructor( selector ) {
    const $countdown = document.querySelector(selector), countdown = $countdown.querySelector('.countdown');
    
    let countDownDate = new Date(countdown.dataset.date + ' GMT' + countdown.dataset.offset + '00').getTime(),
        interval = null;
        
    interval = setInterval(function() {
      var now      = new Date().getTime(),
          distance = countDownDate - now,
          past     = distance < 0 || isNaN(distance),
          days     = past ? 0 : Math.floor(distance / (1000 * 60 * 60 * 24)),
          hours    = past ? 0 : Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
          minutes  = past ? 0 : Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)),
          seconds  = past ? 0 : Math.floor((distance % (1000 * 60)) / 1000);
          
      // When finished
        if ( past ) {
          clearInterval(interval);
          var $onFinish = document.querySelectorAll('.onFinish');
          
          $onFinish.forEach(function(el) {
            el.classList.remove('visible');
            el.classList.remove('onFinish');
            setTimeout(function() {
              el.classList.add('visible');
            }, 500)
          });
        }
        
        // Update Display
          countdown.querySelector(".days").innerHTML    = days;
          countdown.querySelector(".hours").innerHTML   = hours;
          countdown.querySelector(".minutes").innerHTML = minutes;
          countdown.querySelector(".seconds").innerHTML = seconds;
      }, 1000);
  }
}
