import ShoppingListRow from './shopping-list-row';
import ShoppingListStringProductKitCell from '../cell/product-kit/shoppinglist-string-product-kit-cell';
import ShoppingListHtmlTemplateProductKitCell from '../cell/product-kit/shoppinglist-html-template-product-kit-cell';
import ShoppinglistProductKitItemSelectRowCell from '../cell/product-kit/shoppinglist-product-kit-item-select-row-cell';
import ShoppingListItemProductKitCell from '../cell/product-kit/shoppinglist-item-product-kit-cell';
import ShoppingListLineItemProductKitCell from '../cell/product-kit/shoppinglist-line-item-product-kit-cell';

const ShoppingListProductKitSubItemRow = ShoppingListRow.extend({
    cellConstructorMap: {
        ...ShoppingListRow.prototype.cellConstructorMap,
        'shoppinglist-string': ShoppingListStringProductKitCell,
        'shoppinglist-html-template': ShoppingListHtmlTemplateProductKitCell,
        'item': ShoppingListItemProductKitCell,
        'massAction': ShoppinglistProductKitItemSelectRowCell,
        'quantity': ShoppingListLineItemProductKitCell
    },

    constructor: function ShoppingListProductKitSubItemRow(options) {
        ShoppingListProductKitSubItemRow.__super__.constructor.call(this, options);
    },

    renderItem(column) {
        const cellView = ShoppingListProductKitSubItemRow.__super__.renderItem.call(this, column);
        const columnName = cellView.column.get('name');

        if (columnName === 'inventoryStatus') {
            cellView.$el.hide();
        }

        return cellView;
    }
});

export default ShoppingListProductKitSubItemRow;
