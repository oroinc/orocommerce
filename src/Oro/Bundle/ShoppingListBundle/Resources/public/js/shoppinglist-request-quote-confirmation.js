define(function(require) {
    'use strict';

    const StandartConfirmation = require('oroui/js/standart-confirmation');
    const mediator = require('oroui/js/mediator');

    const ShoppingListRequestQuoteConfirmation = StandartConfirmation.extend({
        open() {
            if (this.isConfirmationNeeded()) {
                this.trigger('ok');
                return;
            }
            ShoppingListRequestQuoteConfirmation.__super__.open.call(this);
        },

        isConfirmationNeeded() {
            let skipConfirm;

            try {
                skipConfirm = !mediator.execute('shoppinglist:hasEmptyMatrix');
            } catch (e) {
                skipConfirm = !this.options.hasEmptyMatrix;
            }

            return skipConfirm;
        }
    });

    return ShoppingListRequestQuoteConfirmation;
});
