import StringCell from 'oro/datagrid/cell/string-cell';

const ShoppingListStringCell = StringCell.extend({
    constructor: function ShoppingListStringCell(options) {
        ShoppingListStringCell.__super__.constructor.call(this, options);
    },

    _attributes() {
        const attrs = {};

        if (this.model.get('isConfigurable') && !this.model.get(this.column.get('name'))) {
            attrs['aria-label'] = null;
            attrs['data-blank-content'] = null;
            attrs['aria-colindex'] = null;
        }

        return attrs;
    }
});

export default ShoppingListStringCell;
