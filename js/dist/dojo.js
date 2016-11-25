var dojoCheckout;

(function() {
    var DojoCheckout = function() {};
    DojoCheckout.prototype.setLineItems = function(lineItems) {
        var $ = jQuery;
        var total = 0;
        $(".dojo-checkout-item").not(".dojo-template").remove();
        if (null === lineItems || 0 == lineItems.length) {
            $(".dojo-checkout-items").hide();
        } else {
            $(".dojo-checkout-items").show();
            for (var index in lineItems) {
                var lineItem = lineItems[index];
                var price = parseInt(lineItem.amount_cents) / 100;
                total += price;
                var $item = $(".dojo-template.dojo-checkout-item").clone();
                $item.removeClass("dojo-template");
                $item.find(".dojo-item-description").text(lineItem.description);
                $item.find(".dojo-item-amount").text("$" + price.toFixed(2));
                $item.attr("data-id", lineItem.id);
                $item.data("line-item", lineItem);
                $(".dojo-checkout-items .dojo-totals").before($item);
                $item.show();
            }
            $(".dojo-totals .dojo-item-amount").text("$" + total.toFixed(2));
        }
    };
    DojoCheckout.prototype.getLineItems = function() {
        var $ = jQuery;
        var lineItems = [];
        $(".dojo-checkout-item").not(".dojo-template").each(function() {
            lineItems.push($(this).data("line-item"));
        });
        return lineItems;
    };
    dojoCheckout = new DojoCheckout();
    jQuery(function() {
        dojoCheckout.setLineItems(dojo.param("line_items"));
    });
})();

var dojo;

(function() {
    var Dojo = function(params) {
        this.params = params;
    };
    Dojo.prototype.ajax = function(target, method) {
        var self = this;
        var id = target + "::" + method;
        if (self.params.ajax.hasOwnProperty(id)) {
            return self.params.ajax[id];
        }
        console.error("Attempt to get unknown Dojo ajax endpoint", id);
        return "#";
    };
    Dojo.prototype.param = function(name) {
        var self = this;
        if (self.params.params.hasOwnProperty(name)) {
            return self.params.params[name];
        }
        return null;
    };
    dojo = new Dojo(dojo_params);
})();

jQuery(function($) {
    var DojoPricePlan = function(container) {
        var self = this;
        self.container = container;
        self.id = container.attr("data-id");
        container.find(".price-count").change(function() {
            self.onCountChange($(this));
        });
        container.find("input[type=checkbox]").change(function() {
            if ($(this).is(":checked")) {
                container.find(".simple-pricing").hide();
                container.find(".family-pricing").show();
            } else {
                container.find(".family-pricing").hide();
                container.find(".simple-pricing").show();
            }
        });
    };
    DojoPricePlan.prototype.onCountChange = function(select) {
        var self = this;
        var ruleBlock = select.closest(".form-table");
        var rule = parseInt(ruleBlock.attr("data-rule"));
        if (select.val() != 0) {
            rule++;
            var next = self.container.find('table[data-rule="' + rule + '"]');
            if (0 == next.length) {
                var newBlock = ruleBlock.clone();
                ruleBlock.after(newBlock);
                newBlock.find("input").val("");
                newBlock.find("input").attr("name", self.id + "_price_" + rule);
                newBlock.find(".price-count").val("0");
                newBlock.find(".price-count").attr("name", self.id + "_count_" + rule);
                newBlock.find(".dojo-plan-cost").text("then cost");
                newBlock.find(".dojo-plan-for").text("for next");
                newBlock.attr("data-rule", rule);
                newBlock.find(".price-count").change(function() {
                    self.onCountChange($(this));
                });
            } else {
                next.show();
                self.onCountChange(next.find("select"));
            }
        } else {
            ruleBlock.nextAll().hide();
        }
    };
    $(".dojo-price-plan").each(function() {
        new DojoPricePlan($(this));
    });
});