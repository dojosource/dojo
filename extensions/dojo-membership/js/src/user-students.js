jQuery(function($) {
  $('.dojo-user-students .dojo-add-student').click(function() {
    window.location = dojo.param('students_edit_url');
  });

  $('.dojo-user-students .dojo-select-list-item').click(function() {
    var id = $(this).attr('data-id');
    window.location = dojo.param('students_edit_url') + '?student=' + id;
  });

  $('.dojo-user-students .dojo-enroll').click(function() {
    window.location = dojo.param('enroll_url');
  });
});

