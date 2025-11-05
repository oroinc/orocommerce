import StandartConfirmation from 'oroui/js/standart-confirmation';
import mediator from 'oroui/js/mediator';

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

export default ShoppingListRequestQuoteConfirmation;
