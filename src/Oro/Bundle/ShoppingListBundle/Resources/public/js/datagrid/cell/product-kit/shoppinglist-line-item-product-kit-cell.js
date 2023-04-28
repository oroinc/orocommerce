import ShoppingListLineItemCell from '../shoppinglist-line-item-cell';

const ShoppingListLineItemProductKitCell = ShoppingListLineItemCell.extend({
    disableEditing: true,

    constructor: function ShoppingListLineItemProductKitCell(...args) {
        ShoppingListLineItemProductKitCell.__super__.constructor.apply(this, args);
    }
});

export default ShoppingListLineItemProductKitCell;
