/* global vars_api */
/* exported dlAPI */

class dlAPI {
  constructor() {
    this.api      = window.location.protocol + '//' + window.location.host + '/wp-json/api/v1';
    this.data     = false;
    this.formData = false;
    
    this.el  = {
      $buttonSubmit: null,
      $content: document.querySelector('.content'),
      $error: document.querySelector('.error'),
      $form: document.querySelector('form[data-endpoint]'),
      $messages: document.querySelector('.tml-messages'),
      $submit: document.querySelector('button[type="submit"]')
    };
    
    this.listeners();
  }
  error( data ) {
    if ( data.error ) {
      var errors = typeof data.error == 'string' ? data.error : data.error.join(', ');
      
      if ( this.el.$error ) this.el.$error.innerText = errors;
      else this.el.$messages.innerHTML = '<li class="tml-error">'+data.error+'</li>';
    }
  }
  listeners() {
    this.listen_ajax();
    this.listen_button();
    this.listen_form();
  }
  log() {
    var formData = new FormData(this.el.$form);
    
    for (var pair of formData.entries()) {
      console.log(pair[0]+ ', ' + pair[1]); 
    }
  }
  listen_ajax() {
    jQuery('.ajax').on('click', (e) => {
      e.preventDefault();
      
      var message = e.target.dataset.confirm || 'Are you sure?';
      
      if ( confirm(message) ) {
        var callback = e.target.callback || 'reload';
        
        dl.query(e.target.dataset.endpoint, e.target.dataset.data, 'POST', callback);
      }
      
      return false;
    });
  }
  listen_button() {
    var _this = this;
    
    if ( this.el.$content ) {
      this.el.$content.addEventListener('click', function(e) {
        if ( e.target.tagName === 'BUTTON' && e.target.dataset.action ) {
          _this.el.$buttonSubmit = e.target;
          _this[e.target.dataset.action](e.target);
        }
      });
    }
  }
  listen_form() {
    if ( this.el.$form ) {
      if ( this[this.el.$form.dataset.init] ) this[this.el.$form.dataset.init]();
      
      this.el.$form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        if ( this.el.$form.checkValidity() ) {
          var confirmed = this.el.$form.dataset.confirm == 'true' ? confirm('Are you sure?') : true;
          
          if ( confirmed ) {
            e.target.classList.add('form--sending');
            this.el.$submit.classList.add('button--sending');
            
            this.formData = new FormData(e.target);
            
            this.query(e.target.dataset.endpoint, this.formData, this.el.$form.getAttribute('method'), e.target.dataset.callback);
          }
        }
        else {
          this.el.$form.reportValidity();
          this.error({error: 'All fields are required.'});
        }
        
        return false;
      });
      
      this.el.$form.addEventListener('click', (e) => {
        if ( e.target.tagName === 'BUTTON' && e.target.dataset.action ) {
          this[e.target.dataset.action](e.target);
        }
      });
    }
  }
  query( endpoint, data, method, callback ) {
    var url = this.api + '/' + endpoint, xhr = new XMLHttpRequest();
    
    if ( this.data ) {
      Object.entries(this.data).forEach((d) => {
        data.append(d[0], d[1]);
      });
    }
    
    if ( 'get' == method.toLocaleLowerCase() ) {
      url += '?';
      
      for (var pair of data.entries()) url += '&' + pair[0] + "=" + pair[1];
    }
    
    xhr.open(method, url, true);
    xhr.setRequestHeader('X-WP-Nonce', vars_api.nonce);
    xhr.addEventListener('readystatechange', () => {
      if (xhr.readyState == 4 && xhr.status == 200) {
        var results = JSON.parse(xhr.responseText);
        
        if ( results === false ) this.error({error: 'Request failed.'});
        else if ( results.error ) this.error(results);
        else if ( callback ) this[callback](results);
        
        if ( this.el.$submit ) this.el.$submit.classList.remove('button--sending');
        if ( this.el.$form ) this.el.$form.classList.remove('form--sending');
      }
      
      return false;
    });
    
    xhr.send(data);
  }
  reload() {
    location.reload();
  }
  show( target ) {
    var div = target.closest('.event'),
        el  = div.querySelector('.' + target.dataset.target),
        cl  = target.dataset.toggle.split(' ');
        
    cl.map(v=> target.classList.toggle(v) );
    el.classList.toggle('games--open');
  }
}