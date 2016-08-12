jQuery(function($) {

  $('#dojo-register').click(function() {
    $(this).hide();
    $('.dojo-checkout-items').show('fast');
    console.log($('.dojo-register').data('params'));
  });

  function setVisibility() {
    if ($('#enable-registration').is(':checked')) {
      $('.registration-options').show();
    }
    else {
      $('.registration-options').hide();
    }

    if ($('#limit-registration').is(':checked')) {
      $('.reg-limit').show();
    }
    else {
      $('.reg-limit').hide();
    }

    if ($('#take-payment').is(':checked')) {
      $('.price-rules').show();
    }
    else {
      $('.price-rules').hide();
    }

    for (var rule = 1; rule <= 5; rule++) {
      var $rule = $('table[data-rule=' + rule + ']');
      $rule.show();

      var price_count = $rule.find('.price-count').val();
      if (price_count == 0) {
        for (var followingRule = rule + 1; followingRule <= 5; followingRule++) {
          $rule = $('table[data-rule=' + followingRule + ']');
          $rule.hide();
          $rule.find('input').val('');
          $rule.find('.price-count').val('0');
        }
        break;
      }
    }
  }

  setVisibility();

  $('#event-date').datepicker();

  $('.price-count').change(function() {
    var rule = parseInt($(this).closest('.form-table').attr('data-rule'));
    if ($(this).val() != 0) {
      if (++rule <= 5) {
        $('table[data-rule="' + rule + '"]').show();
      }
    }
    else {
      setVisibility();
    }
  });

  $('#enable-registration').change(function() {
    if ($(this).is(':checked')) {
      $('.registration-options').show('fast');
    }
    else {
      $('.registration-options').hide('fast');
    }
  });

  $('#limit-registration').change(function() {
    if ($(this).is(':checked')) {
      $('.reg-limit').show();
    }
    else {
      $('.reg-limit').hide();
    }
  });

  $('#take-payment').change(function() {
    if ($(this).is(':checked')) {
      $('.price-rules').show();
    }
    else {
      $('.price-rules').hide();
    }
  });
    
});

