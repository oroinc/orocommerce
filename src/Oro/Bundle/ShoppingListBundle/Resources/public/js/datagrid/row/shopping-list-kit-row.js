import Row from 'orodatagrid/js/datagrid/row';

const ShoppingListKitRow = Row.extend({
    constructor: function ShoppingListKitRow(options) {
        ShoppingListKitRow.__super__.constructor.call(this, options);
    },

    renderItem(column) {
        const cellView = Row.__super__.renderItem.call(this, column);

        if (this.model.get('_isKitItemLineItem')) {
            const columnName = cellView.column.get('name');

            if (columnName === 'item') {
                cellView.$el.attr('colspan', 2);
            }

            if (columnName === 'inventoryStatus') {
                cellView.$el.hide();
            }
        }

        return cellView;
    }
});

export default ShoppingListKitRow;
