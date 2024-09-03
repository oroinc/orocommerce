import ShoppingListSelectRowCell from 'oroshoppinglist/js/datagrid/cell/select-row-cell';

const ShoppinglistProductKitItemSelectRowCell = ShoppingListSelectRowCell.extend({
    constructor: function ShoppingListProductKitSelectRowCell(options) {
        return ShoppingListProductKitSelectRowCell.__super__.constructor.call(this, options);
    },

    _attributes() {
        return {
            'aria-label': null,
            'data-blank-content': null,
            'aria-colindex': null
        };
    }
});

export default ShoppinglistProductKitItemSelectRowCell;
