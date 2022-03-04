/* global Choices, vars_api */
/* exported dlAPI */

class apiFormStats extends dlAPI {
  constructor( players ) {
    super();
    this.players = players;
  }
  stats() {
    this.data = new FormData();
    this.maps = 0;
    
    this.el = Object.assign({
      buttonAdd: document.querySelector('button.add'),
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
    
    this.el.buttonAdd.addEventListener('click', () => {
      var valid = this.el.$form.checkValidity();
      
      if ( valid ) this.stats_data();
      else this.el.buttonErrors.click();
    });
    
    this.el.buttonSubmit.addEventListener('click', () => {
      if ( this.maps >= 2 ) {
        this.el.$form.classList.add('form--sending');
        this.el.buttonSubmit.classList.add('button--sending');
        
        this.query(this.el.$form.dataset.endpoint, this.data, this.el.$form.getAttribute('method'), this.el.$form.dataset.callback);
      }
      else alert("You need to enter in at least two maps.");
    });
  }
  stats_data() {
    var formData = new FormData(this.el.$form),
        data     = JSON.stringify(Object.fromEntries(formData)),
        mode     = JSON.parse(this.el.map.selectedOptions[0].dataset.customProperties).mode;
        
    for (var pair of formData.entries()) {
      if ( pair[1] === '' ) continue;
      
      var path  = pair[0].match(/[^\[\]]+/g).filter(Boolean),
          area  = path.shift(),
          field = pair[0].replace(area, mode+'['+area+']');
          
      this.data.append(field, pair[1]);
    }
    
    var el = document.createElement('li');
    el.innerHTML = JSON.stringify(data);
    
    this.el.matchData.append(el);
    
    this.maps++;
    
    this.stats_reset({action: 'next'});
  }
  stats_players() {
    this.players.forEach((c) => {
      var value = c[0] +'|'+ c[1].rank;
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
    window.location = '/stats/match/'
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
  stats_reset( data ) {
    var teams = [];
    teams.push(this.el.teams[0].selectedOptions[0].value);
    teams.push(this.el.teams[1].selectedOptions[0].value);
    
    this.el.$form.reset();
    
    if ( data.action == 'next' ) {
      for (var i = 0; i < 2; i++) {
        this.choices_team[i].setChoiceByValue(teams[i]);
        
        var choice = { choice: this.choices_team[i].getValue() },
            event  = new CustomEvent('choice', {detail: choice } );
            
        this.el.teams[i].dispatchEvent(event);
      }
    }
    else {
      this.data = new FormData();
      this.el.matchData.innerHTML = '';
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
}

/*
  Run Tests
  
  dl.stats_data();
  dl.query(dl.el.$form.dataset.endpoint, dl.data, dl.el.$form.getAttribute('method'), dl.el.$form.dataset.callback);
  
*/
