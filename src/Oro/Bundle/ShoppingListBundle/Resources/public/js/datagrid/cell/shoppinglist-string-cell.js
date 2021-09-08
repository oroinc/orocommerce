import StringCell from 'oro/datagrid/cell/string-cell';
import {isFunction} from 'underscore';

const ShoppingListStringCell = StringCell.extend({
    constructor: function ShoppingListStringCell(options) {
        ShoppingListStringCell.__super__.constructor.call(this, options);
    },

    attributes() {
        let attrs = ShoppingListStringCell.__super__.attributes || {};

        if (isFunction(attrs)) {
            attrs = attrs.call(this);
        }

        if (this.model.get('isConfigurable') && !this.model.get(this.column.get('name'))) {
            attrs['aria-colindex'] = null;
        }

        return attrs;
    }
});

export default ShoppingListStringCell;
