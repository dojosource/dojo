var dojoCheckout;

(function() {

  var DojoCheckout = function() {
  };

  DojoCheckout.prototype.setLineItems = function(lineItems) {
    var $ = jQuery;
    var total = 0;
    $('.dojo-checkout-item').not('.dojo-template').remove();

    if (null === lineItems || 0 == lineItems.length) {
      $('.dojo-checkout-items').hide();
    }
    else {
      $('.dojo-checkout-items').show();
      for (var index in lineItems) {
        var lineItem = lineItems[index];
        var price = parseInt(lineItem.amount_cents) / 100;
        total += price;
        var $item = $('.dojo-template.dojo-checkout-item').clone();
        $item.removeClass('dojo-template');
        $item.find('.dojo-item-description').text(lineItem.description);
        $item.find('.dojo-item-amount').text('$' + price.toFixed(2));
        $item.attr('data-id', lineItem.id);
        $item.data('line-item', lineItem);
        $('.dojo-checkout-items .dojo-totals').before($item);
        $item.show();
      }
      $('.dojo-totals .dojo-item-amount').text('$' + total.toFixed(2));
    }
  };

  DojoCheckout.prototype.getLineItems = function() {
    var $ = jQuery;
    var lineItems = [];
    $('.dojo-checkout-item').not('.dojo-template').each(function () {
      lineItems.push($(this).data('line-item'));
    });
    return lineItems;
  };

  dojoCheckout = new DojoCheckout();

  jQuery(function() {
    dojoCheckout.setLineItems(dojo.param('line_items'));
  });

})();


