import Row from 'orodatagrid/js/datagrid/row';

const ShoppingListRow = Row.extend({
    constructor: function ShoppingListRow(options) {
        ShoppingListRow.__super__.constructor.call(this, options);
    },

    initialize(options) {
        // let descendants override itemView
        if (!this.itemView) {
            // itemView function is called as new this.itemView
            // it is placed here to pass THIS within closure
            const rowView = this;
            this.itemView = function(options) {
                const renderColumnName = rowView.model.get('renderColumnName');
                const definitionColumnName = rowView.model.get('definitionColumnName');
                const definitionColumn =
                    renderColumnName && options.model.get('name') === renderColumnName &&
                    definitionColumnName && rowView.columns.find(column => column.get('name') === definitionColumnName);
                // substitute current column with definition column if it's available
                const column = definitionColumn || options.model;
                const cellOptions = rowView.getConfiguredCellOptions(column);
                cellOptions.model = rowView.model;
                const Cell = column.get('cell');
                return new Cell(cellOptions);
            };
        }

        ShoppingListRow.__super__.initialize.call(this, options);
    },

    renderItem(column) {
        const cellView = Row.__super__.renderItem.call(this, column);
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

        return ShoppingListRow.__super__.insertView.call(this, column, ...rest);
    }
});

export default ShoppingListRow;
