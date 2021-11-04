define(function(require) {
    'use strict';

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
            return !!this.options.hasEmptyMatrix;
        }
    });

    return ShoppingListRequestQuoteConfirmation;
});
