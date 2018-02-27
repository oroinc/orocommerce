define(function(require) {
    'use strict';

    var _ = require('underscore');
    var StandartConfirmation = require('oroui/js/standart-confirmation');
    var mediator = require('oroui/js/mediator');

    var ShoppingListRequestQuoteConfirmation;

    ShoppingListRequestQuoteConfirmation = StandartConfirmation.extend({
        open: function() {
            var lineItems = mediator.execute('get-line-items');
            var lineItemsCount = lineItems.filter(function(lineItem) {
                if (lineItem.$el.attr('class').indexOf('--configurable') !== -1) {
                    var quantities = 0;
                    _.each(lineItem.$elements.quantity, function(quantity) {
                        quantities += quantity.value ? parseInt(quantity.value) : 0;
                    });
                    return quantities !== 0;
                } else {
                    return true;
                }
            }).length;

            if (
                (this.options.hasEmptyMatrix && lineItemsCount === 0) ||
                (!this.options.hasEmptyMatrix && lineItemsCount > 0)
            ) {
                this.trigger('ok');
                return;
            }
            ShoppingListRequestQuoteConfirmation.__super__.open.call(this);
        }
    });

    return ShoppingListRequestQuoteConfirmation;
});
