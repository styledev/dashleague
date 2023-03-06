/* global Choices, vars_api */
/* exported dlAPI */

class apiFormStats extends dlAPI {
  error( data ) {
    if ( data.error ) {
      var errors = typeof data.error == 'string' ? data.error : data.error.join(', ');
      
      if ( this.el.$error ) this.el.$error.innerText = errors;
      else this.el.$messages.innerHTML = '<li class="tml-error">'+data.error+'</li>';
      
      this.el.$form.classList.remove('form--sending');
      this.el.buttonSubmit.classList.remove('button--sending');
    }
  }
  stats() {
    this.data = {};
    // this.maps = 0;
    this.players = players;
    
    this.el = Object.assign({
      buttonErrors: document.querySelector('button.errors'),
      buttonSubmit: document.querySelector('button.submit'),
      map: document.querySelector('select.map'),
      matchData: document.querySelector('.match-data'),
      outcome: document.querySelectorAll('select.outcome'),
      players: document.querySelectorAll('select.player'),
      points: document.querySelectorAll('input.points'),
      time: document.querySelectorAll('input.time'),
      selects: document.querySelectorAll('select.choices'),
      teams: document.querySelectorAll('select.team')
    }, this.el);
    
    this.choices_team = [];
    this.choices_data = { players: [], outcome: [], team_0: [], team_1: [] };
    
    this.stats_players();
    this.stats_outcome();
    this.stats_team();
    
    this.el.selects.forEach(function(el) {
      var searchEnabled = el.dataset.searchenabled && el.dataset.searchenabled === 'false' ? false : true;
      
      var choice = new Choices(el, {
        fuseOptions: { threshold: 0.2 },
        resetScrollPosition: false,
        searchEnabled: searchEnabled,
        searchFloor: 1,
        searchResultLimit: 50
      });
      
      choice.containerOuter.element.addEventListener("focus", function() { choice.showDropdown(); });
    });
    
    this.el.map.addEventListener('choice', (e) => {
      var props = JSON.parse(e.detail.choice.customProperties);
      
      this.el.time.forEach(function(el) {
        if ( props.mode === 'payload' ) {
          el.classList.remove('hide');
          el.required = true;
        }
        else {
          el.classList.add('hide');
          el.required = false;
        }
      });
      
      this.el.points.forEach(function(el) {
        if ( props.mode !== 'payload' ) el.required = true;
        else el.required = false;
      });
    });
    
    this.el.buttonSubmit.addEventListener('click', () => {
        this.el.$form.classList.add('form--sending');
        this.el.buttonSubmit.classList.add('button--sending');
        
        this.stats_data();
        
        this.query(this.el.$form.dataset.endpoint, this.data, this.el.$form.getAttribute('method'), this.el.$form.dataset.callback);
    });
  }
  deepSet(obj, path, value) {
      if (Object(obj) !== obj) return obj; // When obj is not an object
      // If not yet an array, get the keys from the string-path
      if (!Array.isArray(path)) path = path.toString().match(/[^.[\]]+/g) || []; 
      path.slice(0,-1).reduce((a, c, i) => // Iterate all of them except the last one
           Object(a[c]) === a[c] // Does the key exist and is its value an object?
               // Yes: then follow that path
               ? a[c] 
               // No: create the key. Is the next key a potential array-index?
               : a[c] = Math.abs(path[i+1])>>0 === +path[i+1] 
                     ? [] // Yes: assign a new array object
                     : {}, // No: assign a new plain object
           obj)[path[path.length-1]] = value; // Finally assign the value to the last key
      return obj; // Return the top-level object to allow chaining
  }
  stats_data() {
    var formData = new FormData(this.el.$form),
        data     = {};
        
    for (const [path, value] of formData) this.deepSet(data, path, value);
    
    this.data = data;
  }
  stats_players() {
    this.players.forEach((c) => {
      var value = c[0];// +'|'+ c[1].rank;
      
      this.choices_data.players.push({'value': value, 'label': c[1].name, customProperties: {
        team: c[1].team
      }});
    });
    
    this.el.players.forEach((el) => {
      var choice = new Choices(el, {
        fuseOptions: { threshold: 0.2 },
        resetScrollPosition: false,
        searchFloor: 1,
        searchResultLimit: 100,
        choices: []
      });
      
      choice.containerOuter.element.addEventListener("focus", function() { choice.showDropdown(); });
      
      this.choices_data[el.dataset.team].push(choice);
    });
  }
  stats_finish() {
    this.el.$form.classList.remove('form--sending');
    this.el.buttonSubmit.classList.remove('button--sending');
    
    window.location = '/stats-add-map/';
  }
  stats_outcome() {
    this.el.outcome.forEach((el) => {
      var choice = new Choices(el, {
        fuseOptions: { threshold: 0.2 },
        resetScrollPosition: false,
        searchFloor: 1,
        searchResultLimit: 100
      });
      
      choice.containerOuter.element.addEventListener("focus", function() { choice.showDropdown(); });
      
      this.choices_data.outcome.push(choice);
      
      el.addEventListener('choice', (e) => {
        var index  = Array.prototype.indexOf.call(this.el.outcome, e.target),
            target = index === 0 ? 1 : 0,
            value  = '';
            
        switch (e.detail.choice.value) {
          case '1': value = '0'; break;
          case '0': value = '1'; break;
        }
        
        this.choices_data.outcome[target].setChoiceByValue(value);
      });
    });
  }
  stats_reset() {
    var teams = [];
    
    teams.push(this.el.teams[0].selectedOptions[0].value);
    teams.push(this.el.teams[1].selectedOptions[0].value);
    
    this.el.$form.reset();
    
    for (var i = 0; i < 2; i++) {
      this.choices_team[i].setChoiceByValue(teams[i]);
      
      var choice = { choice: this.choices_team[i].getValue() },
          event  = new CustomEvent('choice', {detail: choice } );
          
      this.el.teams[i].dispatchEvent(event);
    }
  }
  stats_team() {
    this.el.teams.forEach((el) => {
      var choice = new Choices(el, {
        fuseOptions: { threshold: 0.2 },
        resetScrollPosition: false,
        searchFloor: 1,
        searchResultLimit: 100
      });
      
      choice.containerOuter.element.addEventListener("focus", function() { choice.showDropdown(); });
      
      this.choices_team.push(choice);
      
      el.addEventListener('choice', (e) => {
        var clear    = e.detail.choice.value === '',
            value    = e.detail.choice.label,
            filtered = clear ? [] : this.choices_data.players.filter((player) => {
              return player.customProperties.team == value;
            });
            
        this.choices_data[e.target.id].forEach(function(dropdown) {
          var items = dropdown.config.choices.concat(filtered);
          dropdown.setChoices(items, 'value', 'label', true).setChoiceByValue('');
        });
      });
    });
  }
  query( endpoint, data, method, callback ) {
    var url = this.api + '/' + endpoint, xhr = new XMLHttpRequest();
    
    xhr.open(method, url, true);
    xhr.setRequestHeader('X-WP-Nonce', vars_api.nonce);
    
    xhr.addEventListener('readystatechange', () => {
      if (xhr.readyState == 4 && xhr.status == 200) {
        var results = JSON.parse(xhr.responseText);
        
        if ( this.el.$submit ) this.el.$submit.classList.remove('button--sending');
        if ( this.el.$form ) this.el.$form.classList.remove('form--sending');
        
        if ( results === false ) this.error({error: 'Request failed.'});
        else if ( results.error ) this.error(results);
        else if ( callback ) this[callback](results);
      }
    });
    
    xhr.send(JSON.stringify(data));
  }
}

/*
  Run Tests
  
  dl.stats_data();
  dl.query(dl.el.$form.dataset.endpoint, dl.data, dl.el.$form.getAttribute('method'), dl.el.$form.dataset.callback);
  
*/
