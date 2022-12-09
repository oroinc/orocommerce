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

    filterer(item) {
        if (!this.model.get('renderColumnName')) {
            return true;
        }

        return this.model.get('renderColumnName') === item.get('name');
    },

    filterCallback(view, included) {
        const {$el} = view;
        if (view.model.get('renderColumnName')) {
            if (included) {
                const visibleColumns = this.columns.filter(column => column.get('renderable'));
                const start = visibleColumns.findIndex(
                    column => column.get('name') === view.model.get('renderColumnName')
                );

                $el.attr('colspan', visibleColumns.length - start);
            } else {
                $el.empty();
            }
        }

        return $el;
    },

    insertView(...args) {
        const subviews = [...this.subviews];
        subviews.pop();

        const columnName = this.model.get('definitionColumnName') || this.model.get('renderColumnName');

        const cellView = subviews.find(subview => subview.column.get('name') === columnName);

        if (cellView) {
            return;
        }

        return ShoppingListRow.__super__.insertView.call(this, ...args);
    }
});

export default ShoppingListRow;
