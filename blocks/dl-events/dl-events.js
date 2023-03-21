window.addEventListener('DOMContentLoaded', (event) => {
  var events       = document.querySelectorAll('.event__datetime'),
      options_date = { day: "2-digit", month: 'long' },
      options_time = { hour: 'numeric', minute: '2-digit', timeZoneName: 'short' };
      
  if ( events && events[1].dataset.user == 0 ) {
    for (i = 0; i < events.length; ++i) {
      var date = new Date(Date.parse(events[i].dataset.date)),
          dt = events[i].querySelector('.event__date');
          tz = events[i].querySelector('.event__time');
          
      dt.innerText = date.toLocaleString('en-US', options_date);
      tz.innerText = date.toLocaleString('en-US', options_time).replace(' ', '').replace('AM', 'am').replace('PM', 'pm');
    }
  }
});