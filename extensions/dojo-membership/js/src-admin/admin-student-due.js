jQuery(function($) {
  $('.dojo-student-due .payment-received').click(function() {
    var data = {
      student: dojo.param('student_id')
    };
    $.post(dojo.ajax('membership', 'record_payment_received'), data, function() {
      window.location.reload();
    });
  });
});
