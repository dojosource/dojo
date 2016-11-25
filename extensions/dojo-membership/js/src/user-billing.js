jQuery(function($) {
  $('.dojo-user-billing .dojo-save-billing').click(function() {
    var data = {};
    $('.dojo-billing-options input, .dojo-billing-options select').each(function() {
      if ($(this).attr('type') == 'radio') {
        if ($(this).is(':checked')) {
          data[$(this).attr('name')] = $(this).val();
        }
      }
      else {
        data[$(this).attr('name')] = $(this).val();
      }
    });
    $.post(dojo.ajax('membership', 'save_billing_options'), data, function(response) {
      if (response == 'success') {
        window.location = dojo.param('membership_url');
      }
      else {
        $('.dojo-billing-error .dojo-error').text(response);
        $('.dojo-billing-error').show();
      }
    });
  });
});


