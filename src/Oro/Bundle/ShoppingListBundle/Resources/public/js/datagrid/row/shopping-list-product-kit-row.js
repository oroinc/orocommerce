import ShoppingListRow from 'oroshoppinglist/js/datagrid/row/shopping-list-row';
import ShoppingListLineItemProductKitCell
    from 'oroshoppinglist/js/datagrid//cell/product-kit/shoppinglist-line-item-product-kit-cell';

const ShoppingListProductKitRow = ShoppingListRow.extend({
    cellConstructorMap: {
        ...ShoppingListRow.prototype.cellConstructorMap,
        quantity: ShoppingListLineItemProductKitCell
    },

    constructor: function ShoppingListProductKitRow(options) {
        ShoppingListProductKitRow.__super__.constructor.call(this, options);
    }
});

export default ShoppingListProductKitRow;
