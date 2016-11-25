jQuery(function($) {
  $('.dojo-user-enroll-apply .submit-application').click(function() {
    var unchecked = $('.terms-checkbox').not(':checked');
    if (unchecked.length) {
      $('.error-message').text('Please indicate that you have read and agree with the terms and conditions for each membership that requires it.');
      $('.error-container').show();
    }
    else {
      $('.error-container').hide();
      $('.submit-application').hide();
      $('.dojo-please-wait').show();
      $('#post').submit();
    }
  });
});
