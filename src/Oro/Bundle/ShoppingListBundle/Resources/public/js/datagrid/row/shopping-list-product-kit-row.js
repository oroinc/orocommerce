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
    },

    onBackgridSelected: function(model, isSelected) {
        ShoppingListProductKitRow.__super__.onBackgridSelected.call(this, model, isSelected);

        this.model.getRelatedModels(this.model.get('ids')).forEach(relatedModel => {
            if (isSelected) {
                relatedModel.classList().add('row-selected');
            } else {
                relatedModel.classList().remove('row-selected');
            }
        });
    }
});

export default ShoppingListProductKitRow;
