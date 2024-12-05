import {noop} from 'underscore';
import ShoppingListSelectRowCell from 'oroshoppinglist/js/datagrid/cell/select-row-cell';

const ShoppingListProductKitSelectRowCell = ShoppingListSelectRowCell.extend({
    constructor: function ShoppingListProductKitSelectRowCell(options) {
        return ShoppingListProductKitSelectRowCell.__super__.constructor.call(this, options);
    },

    updateCheckbox: noop
});

export default ShoppingListProductKitSelectRowCell;
