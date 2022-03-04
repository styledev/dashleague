/* exported dlSettings */

class apiManage extends dlAPI {
  constructor() {
    super();
  }
  submitMatch( target ) {
    var data = JSON.parse(target.dataset.data),
        confirmation = 'Are you absolutely sure?';
        
    // if ( confirm(confirmation) ) {
      var data = new FormData();
      this.el.$buttonSubmit.classList.add('button--sending');
      this.data = JSON.parse(target.dataset.data);
      this.query(target.dataset.endpoint, data, 'POST', target.dataset.callback);
    // }
  }
}

var dl = new apiManage;