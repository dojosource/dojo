jQuery(function($) {
  var today = new Date();
  $('.dojo-user-students-edit .dojo-membership-date').datepicker({
    changeMonth: true,
    changeYear: true,
    yearRange: '1930:' + today.getFullYear()
  });

  $('.dojo-user-students-edit input[name=first_name]').change(function() {
    $('input[name=alias]').val($(this).val());
  });

  $('.dojo-user-students-edit .dojo-cancel-contract button').click(function() {
    $('.dojo-cancel-contract').hide();
    $('.dojo-confirm-cancel').show();
  });

  $('.dojo-user-students-edit .dojo-confirm-cancel a').click(function() {
    var data = {
      'membership_id': dojo.param('membership_id')
    };
    $.post(dojo.ajax('membership', 'cancel_membership'), data, function(response) {
      if (response == 'success') {
        window.location.reload();
      }
      else {
        $('.dojo-cancel-error').text(response);
        $('.dojo-cancel-error-container').show();
      }
    });
  });
});