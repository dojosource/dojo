jQuery(function($) {
  var params = $('.dojo-register').data('params');
  var $itemTemplate = $('.dojo-template.dojo-checkout-item');
  var numReg = 0;

  function addStudent(id) {
    var $item = $itemTemplate.clone();
    $item.removeClass('dojo-template');
    $item.show();

    var price = 20;
    numReg++;

    var student = params.students[id];
    $item.find('.dojo-item-description').text(student.firstName + ' ' + student.lastName + ' Registration');
    $item.attr('data-student', id);
    $item.data('price', price);
    $('.dojo-checkout-items .dojo-totals').before($item);

    updatePrices();
  }

  function removeStudent(id) {
    var student = params.students[id];

    var $item = $('.dojo-checkout-item[data-student=' + id + ']');
    if ($item) {
      numReg--;
    }
    $item.remove();

    updatePrices();
  }

  function updatePrices() {
    var rule = 1;
    var ruleCount = 1;
    var total = 0;
    $('.dojo-checkout-item').not('.dojo-template').each(function() {
      var price = parseFloat(params.price[rule]);
      total += price;
      $(this).find('.dojo-item-amount').text('$' + price.toFixed(2));
      if (ruleCount >= params.price_count[rule]) {
        rule++;
        ruleCount = 1;
      }
      else {
        ruleCount++;
      }
    });
    $('.dojo-totals .dojo-item-amount').text('$' + total.toFixed(2));
  }

  $('.dojo-student-item input').change(function() {
    if ($(this).is(':checked')) {
      addStudent($(this).attr('data-student'));
    }
    else {
      removeStudent($(this).attr('data-student'));
    }

    var remainingItems = $('.dojo-checkout-item').not('.dojo-template');
    if (remainingItems.length == 0) {
      $('.dojo-checkout-items').hide('fast');
    }
    else {
      $('.dojo-checkout-items').show('fast');
    }
  });

  $('#dojo-register').click(function() {
    $(this).hide();
    $('.dojo-pre-register-click').hide();
    $('.dojo-checkout-items').show('fast');
  });

  if (1 == params.num_students) {
    for (var id in params.students) {
      addStudent(id);
    }
  }
});

