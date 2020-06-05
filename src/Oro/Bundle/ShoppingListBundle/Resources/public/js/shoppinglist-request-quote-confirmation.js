define(function(require) {
    'use strict';

    const _ = require('underscore');
    const StandartConfirmation = require('oroui/js/standart-confirmation');
    const mediator = require('oroui/js/mediator');

    const ShoppingListRequestQuoteConfirmation = StandartConfirmation.extend({
        open: function() {
            if (this.isConfirmationNeeded()) {
                this.trigger('ok');
                return;
            }
            ShoppingListRequestQuoteConfirmation.__super__.open.call(this);
        },

        isConfirmationNeeded: function() {
            let skipConfirm;

            try {
                skipConfirm = !mediator.execute('shoppinglist:hasEmptyMatrix');
            } catch (e) {
                // handler isn't defined in mediator, check empty matrix in old way
                skipConfirm = this.isConfirmationNeededFromOldShoppingList();
            }

            return skipConfirm;
        },

        /**
         * Method that works with old shopping list
         *
         * @private
         * @deprecated
         */
        isConfirmationNeededFromOldShoppingList: function() {
            const lineItems = mediator.execute('get-line-items');
            const lineItemsCount = lineItems.filter(function(lineItem) {
                if (lineItem.$el.attr('class').indexOf('--configurable') !== -1) {
                    let quantities = 0;
                    _.each(lineItem.$elements.quantity, function(quantity) {
                        quantities += quantity.value ? parseInt(quantity.value) : 0;
                    });
                    return quantities !== 0;
                } else {
                    return true;
                }
            }).length;

            return (this.options.hasEmptyMatrix && lineItemsCount === 0) ||
                (!this.options.hasEmptyMatrix && lineItemsCount > 0);
        }
    });

    return ShoppingListRequestQuoteConfirmation;
});
