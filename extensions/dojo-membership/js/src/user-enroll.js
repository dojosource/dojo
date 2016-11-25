/* global dojoCheckout */

jQuery(function($) {
  function updateCheckout(doPost) {
    var data = {};
    if (doPost) {
      $('#dojo-enroll select').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });
    }
    else {
      data.refresh_only = true;
    }
    $.post(dojo.ajax('membership', 'save_enrollment'), data, function(response) {
      var data = eval('(' + response + ')');
      dojoCheckout.setLineItems(data.line_items);
      if (0 == data.line_items.length) {
        $('.dojo-monthly-pricing').hide();
        $('.dojo-registration-fee').hide();
      }
      else {
        $('.dojo-monthly-pricing').show();
        $('.dojo-registration-fee').show();
      }
      var reg_fee = parseInt(data.reg_fee) / 100;
      $('.dojo-registration-amount').text('$' + reg_fee.toFixed(2));
    });
  }

  if ($('.dojo-user-enroll').length > 0) {
    $('#dojo-enroll select').change(function() {
      updateCheckout(true);
    });

    $('.dojo-membership-details').click(function() {
      window.location = dojo.param('contract_url') + $(this).attr('data-id');
    });

    // refresh to correct ajax state after browser back button
    updateCheckout(false);
  }
});
