class dlRegister {
  constructor() {
    this.el = {
      $: document.querySelector('form[name="register"]'),
      discord: document.getElementById('discord'),
    };
    
    this.listeners();
  }
  listeners() {
    if ( this.el.discord ) {
      jQuery(document).ready(() => { this.el.discord.focus(); });
    }
  }
}

var register = new dlRegister;