jQuery(function($) {
  $('.dojo-user-membership .dojo-add-student').click(function() {
    window.location = dojo.param('students_edit_url');
  });

  $('.dojo-user-membership .dojo-students .dojo-select-list-item').click(function() {
    var id = $(this).attr('data-id');
    window.location = dojo.param('students_edit_url') + '?student=' + id;
  });

  $('.dojo-user-membership .dojo-enroll').click(function() {
    window.location = dojo.param('enroll_url');
  });
});
