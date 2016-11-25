jQuery(function($) {
  $('#dojo-login').submit(function(e) {
    e.preventDefault();

    var data = {};
    $(this).find('input').each(function() {
      var name = $(this).attr('name');
      var val = $(this).val();
      data[name] = val;
    });

    $('.dojo-error').hide();
    $('.dojo-submit-button').hide();
    $('.dojo-please-wait').show();

    $.post(dojo.ajax('membership', 'login'), data, function(response) {
      if (response != 'success') {
        $('.dojo-error').html(response);
        $('.dojo-error').show();
        $('.dojo-please-wait').hide();
        $('.dojo-submit-button').show();
      }
      else {
        window.location.reload();
      }
    });
  });
});

