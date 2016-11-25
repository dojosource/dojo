jQuery(function ($) {
  $('.dojo-student-contract .dojo-change-contract').click(function() {
    $('.dojo-current-membership').hide();
    $('.dojo-change-membership').show();
  });

  $('.dojo-student-contract .dojo-change-membership select').change(function() {
    $('.dojo-apply-change-contract').show();
  });

  $('.dojo-student-contract .dojo-apply-change-contract').click(function() {
    var data = {
      student: dojo.param('student_id'),
      contract: $('.dojo-student-contract select[name=contract] option:selected').val()
    };
    $.post(dojo.ajax('membership', 'change_student_contract'), data, function() {
      window.location.reload();
    });
  });
});

