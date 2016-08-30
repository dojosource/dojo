function DojoPaymentStripe() {
    var self = this;
    self.listeners = [];
}

DojoPaymentStripe.prototype.init = function(params) {
    var self = this;
    
    self.params = params;

    self.stripeHandler = StripeCheckout.configure({
        key: self.params.stripe_key,
        locale: 'auto',
        token: function(token) {
            self.broadcastEvent('token', { token: token } );
        }
    });

    // Close Checkout on page navigation:
    jQuery(window).on('popstate', function() {
        self.stripeHandler.close();
    });
}

DojoPaymentStripe.prototype.open = function(name, description, email, buttonText) {
    var self = this;

    self.stripeHandler.open({
        name: name,
        description: description,
        email: email,
        panelLabel: buttonText
    });
}

DojoPaymentStripe.prototype.registerListener = function(callback) {
    var self = this;
    self.listeners.push(callback);
}

DojoPaymentStripe.prototype.broadcastEvent = function(event, data) {
    var self = this;
    for (var index = 0; index < self.listeners.length; index++) {
        var callback = self.listeners[index];
        callback(event, data);
    }
}


