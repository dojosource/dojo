jQuery(function($) {

  var DojoPricePlan = function(container) {
    var self = this;

    self.container = container;
    self.id = container.attr('data-id');

    container.find('.price-count').change(function () {
      self.onCountChange($(this));
    });

    container.find('input[type=checkbox]').change(function () {
      if ($(this).is(':checked')) {
        container.find('.simple-pricing').hide();
        container.find('.family-pricing').show();
      }
      else {
        container.find('.family-pricing').hide();
        container.find('.simple-pricing').show();
      }
    });
  };

  DojoPricePlan.prototype.onCountChange = function(select) {
    var self = this;

    var ruleBlock = select.closest('.form-table');
    var rule = parseInt(ruleBlock.attr('data-rule'));
    if (select.val() != 0) {
      rule++;
      var next = self.container.find('table[data-rule="' + rule + '"]');
      if (0 == next.length) {
        var newBlock = ruleBlock.clone();
        ruleBlock.after(newBlock);
        newBlock.find('input').val('');
        newBlock.find('input').attr('name', self.id + '_price_' + rule);
        newBlock.find('.price-count').val('0');
        newBlock.find('.price-count').attr('name', self.id + '_count_' + rule);
        newBlock.find('.dojo-plan-cost').text('then cost');
        newBlock.find('.dojo-plan-for').text('for next');
        newBlock.attr('data-rule', rule);
        newBlock.find('.price-count').change(function () {
          self.onCountChange($(this));
        });
      }
      else {
        next.show();
        self.onCountChange(next.find('select'));
      }
    }
    else {
      ruleBlock.nextAll().hide();
    }
  };

  $('.dojo-price-plan').each(function() {
    new DojoPricePlan($(this));
  });
});