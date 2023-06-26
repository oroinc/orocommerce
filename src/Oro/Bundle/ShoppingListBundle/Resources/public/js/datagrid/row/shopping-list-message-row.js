import ShoppingListRow from './shopping-list-row';

const ShoppingListMessageRow = ShoppingListRow.extend({
    constructor: function ShoppingListMessageRow(...args) {
        ShoppingListMessageRow.__super__.constructor.apply(this, args);
    },

    renderItem(column) {
        const cellView = ShoppingListMessageRow.__super__.renderItem.call(this, column);
        const renderColumnName = this.model.get('renderColumnName');
        if (renderColumnName) {
            if (column.get('name') === renderColumnName) {
                const visibleColumns = this.columns.filter(column => column.get('renderable'));
                const start = visibleColumns.findIndex(column => column.get('name') === renderColumnName);
                cellView.$el.attr('colspan', visibleColumns.length - start);
            } else {
                cellView.$el.empty();
            }
        }

        return cellView;
    },

    insertView(column, ...rest) {
        const columns = this.columns;
        const columnName = this.model.get('renderColumnName');
        if (
            columnName &&
            columns.indexOf(column) > columns.findIndex(column => column.get('name') === columnName)
        ) {
            return;
        }

        return ShoppingListMessageRow.__super__.insertView.call(this, column, ...rest);
    }
});

export default ShoppingListMessageRow;
