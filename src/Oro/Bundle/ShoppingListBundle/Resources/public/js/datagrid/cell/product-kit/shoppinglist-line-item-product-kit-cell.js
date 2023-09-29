import ShoppingListLineItemCell from '../shoppinglist-line-item-cell';

const ShoppingListLineItemProductKitCell = ShoppingListLineItemCell.extend({
    disableEditing: false,

    constructor: function ShoppingListLineItemProductKitCell(...args) {
        ShoppingListLineItemProductKitCell.__super__.constructor.apply(this, args);
    },

    preinitialize(options) {
        const kitHasGeneralError = options.model.get('kitHasGeneralError');
        if (kitHasGeneralError) {
            this.disableEditing = kitHasGeneralError;
        }
    }
});

export default ShoppingListLineItemProductKitCell;
