jQuery(function($) {
  $('.dojo-contracts-edit #cancellation_policy').change(function() {
    if ('days' == $(this).val()) {
      $('.cancellation-days-row').show();
    }
    else {
      $('.cancellation-days-row').hide();
    }
  });
});

