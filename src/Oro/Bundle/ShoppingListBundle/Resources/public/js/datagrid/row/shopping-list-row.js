import $ from 'jquery';
import Row from 'orodatagrid/js/datagrid/row';

const ShoppingListRow = Row.extend({
    useCssAnimation: true,

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

        if (view.model.get('notificationCell')) {
            if (included) {
                const visibleColumns = this.columns.filter(column => column.get('renderable'));
                const start = visibleColumns.findIndex(
                    column => column.get('name') === view.model.get('notificationCell')
                );

                $el.attr('colspan', visibleColumns.length - start);
                $el.attr('data-offset', start);
            } else {
                $el.addClass('hide');
            }
        }

        return $el;
    },

    insertView(...args) {
        const view = ShoppingListRow.__super__.insertView.apply(this, args);

        if (view.$el.data('offset')) {
            view.$el.before($('<td />', {
                'colspan': view.$el.data('offset'),
                'class': 'select-row-cell grid-cell grid-body-cell'
            }));
        }

        return view
    },

    render() {
        ShoppingListRow.__super__.render.call(this);

        if (this.model.get('notificationCell')) {
            this.$('.hide').remove();
        }
    }
});

export default ShoppingListRow;
