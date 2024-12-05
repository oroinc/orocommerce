import ShoppingListRow from 'oroshoppinglist/js/datagrid/row/shopping-list-row';
import ShoppingListLineItemProductKitCell
    from 'oroshoppinglist/js/datagrid//cell/product-kit/shoppinglist-line-item-product-kit-cell';
import ShoppinglistProductKitItemSelectRowCell from '../cell/product-kit/shoppinglist-product-kit-select-row-cell';

const ShoppingListProductKitRow = ShoppingListRow.extend({
    cellConstructorMap: {
        ...ShoppingListRow.prototype.cellConstructorMap,
        quantity: ShoppingListLineItemProductKitCell,
        massAction: ShoppinglistProductKitItemSelectRowCell
    },

    constructor: function ShoppingListProductKitRow(options) {
        ShoppingListProductKitRow.__super__.constructor.call(this, options);
    }
});

export default ShoppingListProductKitRow;
