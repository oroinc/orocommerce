import Row from 'orodatagrid/js/datagrid/row';

const ShoppingListRow = Row.extend({
    constructor: function ShoppingListRow(options) {
        ShoppingListRow.__super__.constructor.call(this, options);
    },

    filterer(item) {
        if (!this.model.get('notificationCell')) {
            return true;
        }

        return this.model.get('notificationCell') === item.get('name');
    },

    filterCallback(view, included) {
        const {$el} = view;

        if (view.model.get('isMessage')) {
            if (included) {
                const visibleColumns = this.columns.filter(column => column.get('renderable'));
                const start = visibleColumns.findIndex(
                    column => column.get('name') === view.model.get('notificationCell')
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

        const messageCell = subviews.find(
            subview => subview.column.get('name') === this.model.get('notificationCell')
        );

        if (messageCell) {
            return;
        }

        return ShoppingListRow.__super__.insertView.call(this, ...args);
    }
});

export default ShoppingListRow;
