jQuery(function($) {
  $('.dojo-membership-student-application .payment-received').click(function() {
    var data = {
      student: dojo.param('student_id')
    };
    $.post(dojo.ajax('membership', 'record_payment_received'), data, function() {
      window.location.reload();
    });
  });

  $('.dojo-membership-student-application .approve-membership').click(function() {
    var data = {
      student: dojo.param('student_id')
    };
    $.post(dojo.ajax('membership', 'approve_application'), data, function() {
      window.location.reload();
    });
  });
});

