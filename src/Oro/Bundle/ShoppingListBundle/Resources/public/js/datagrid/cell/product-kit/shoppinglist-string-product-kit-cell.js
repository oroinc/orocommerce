import StringCell from 'oro/datagrid/cell/string-cell';

const ShoppingListStringProductKitCell = StringCell.extend({
    constructor: function ShoppingListStringProductKitCell(options) {
        ShoppingListStringProductKitCell.__super__.constructor.call(this, options);
    },

    _attributes() {
        if (!this.model.get(this.column.get('name'))) {
            return {
                'aria-label': null,
                'data-blank-content': null,
                'aria-colindex': null
            };
        }
    }
});

export default ShoppingListStringProductKitCell;
