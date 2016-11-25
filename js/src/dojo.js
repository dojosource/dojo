var dojo; // eslint-disable-line no-unused-vars

(function() {

  var Dojo = function(params) {
    this.params = params;
  };

  Dojo.prototype.ajax = function(target, method) {
    var self = this;
    var id = target + '::' + method;
    if (self.params.ajax.hasOwnProperty(id)) {
      return self.params.ajax[id];
    }
    // eslint-disable-next-line no-console
    console.error('Attempt to get unknown Dojo ajax endpoint', id);
    return '#';
  };

  Dojo.prototype.param = function(name) {
    var self = this;
    if (self.params.params.hasOwnProperty(name)) {
      return self.params.params[name];
    }
    return null;
  };

  // dojo_params is supplied via wp_localize_script
  dojo = new Dojo(dojo_params);

})();
