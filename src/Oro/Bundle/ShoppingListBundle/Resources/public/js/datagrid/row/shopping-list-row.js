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
        const $el = ShoppingListRow.__super__.filterCallback.call(this, view, included);

        if (view.model.get('bound')) {
            if (included) {
                const visibleColumns = this.columns.filter(column => column.get('renderable'));
                const start = visibleColumns.findIndex(
                    column => column.get('name') === view.model.get('notificationCell')
                );

                $el.attr('colspan', visibleColumns.length - start);
                $el.attr('data-offset', start);
            } else {
                $el.addClass('hide');
                $el.show();
            }
        }

        return $el;
    },

    insertView(...args) {
        const view = ShoppingListRow.__super__.insertView.apply(this, args);

        if (view.$el.data('offset')) {
            view.$el.prevAll('td').each((index, td) => {
                td.innerHTML = '';
                td.classList.remove('hide');
            });
        }

        return view;
    },

    render() {
        ShoppingListRow.__super__.render.call(this);

        if (this.model.get('notificationCell')) {
            this.$('.hide').remove();
        }
    }
});

export default ShoppingListRow;
