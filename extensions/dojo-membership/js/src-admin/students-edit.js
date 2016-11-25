jQuery(function($) {
  var today = new Date();
  $('.dojo-students-edit input[name=dob]').datepicker({
    changeMonth: true,
    changeYear: true,
    yearRange: '1930:' + today.getFullYear()
  });
});
