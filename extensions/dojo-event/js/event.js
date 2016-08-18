jQuery(function($) {
  var params = $('.dojo-register').data('params');
  var students = [];

  function updatePrices() {
    students = [];
    var remaining_students = [];
    $('.dojo-student-item input').each(function() {
      var student_id = $(this).attr('data-student');
      if ($(this).is(':checked')) {
        students.push(student_id);
      }
      else {
        remaining_students.push(student_id);
      }
    });

    var data = {
      post_id: params.post_id,
      students: students
    };
    $.post(params.get_line_items_url, data, function(response) {
      var lineItems = eval('(' + response + ')');
      dojoCheckoutSetLineItems(lineItems);
      if (students.length > 0) {
        $('.dojo-checkout-container').show();
      }
      else {
        $('.dojo-checkout-container').hide();
      }
      if (students.length >= params.remaining_spots && remaining_students.length) {
        $('.dojo-reg-limit-reached').show();
        $('.dojo-student-item input').not(':checked').attr('disabled', true);
      }
      else {
        $('.dojo-reg-limit-reached').hide();
        $('.dojo-student-item input').attr('disabled', false);
      }
    });
  }

  $('.dojo-student-item input').change(function() {
    updatePrices();
  });

  $('#dojo-complete-registration').click(function() {
    var button = $(this);
    button.hide();
    $('.dojo-please-wait').show();
    data = {
      post_id: params.post_id,
      students: students
    };
    $.post(params.registration_url, data, function(response) {
      $('.dojo-please-wait').hide();
      console.log('resonse', response);
      if (response != 'success') {
        $('.dojo-registration-error').text(response);
        $('.dojo-error-container').show();
        button.show();
      }
      else {
        $('.dojo-error-container').hide();
      }
    });
  });
});

