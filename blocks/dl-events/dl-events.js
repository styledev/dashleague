window.addEventListener('DOMContentLoaded', (event) => {
  var events = document.querySelectorAll('.event__date'),
      options = { hour: 'numeric', minute: '2-digit', timeZoneName: 'short' }
      
  if ( events ) {
    for (i = 0; i < events.length; ++i) {
      var date = new Date(Date.parse(events[i].dataset.date)),
          tz = events[i].querySelector('.event__timezone');
          
      tz.innerText = date.toLocaleString('en-US', options).replace(' ', '').replace('AM', 'am').replace('PM', 'pm');
    }
  }
});