import $ from 'jquery';
import _ from 'underscore';
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

        if (included && view.model.get('notificationCell')) {
            const column = this.columns.find(
                column => column.get('name') === view.model.get('notificationCell')
            );
            const start = column.get('order');

            $el.attr('colspan', this.columns.length - start);
            $el.attr('data-offset', start);
        }

        return $el;
    },

    insertView(...args) {
        const view = ShoppingListRow.__super__.insertView.apply(this, args);

        if (view.$el.data('offset')) {
            view.$el.before($('<td />', {
                'colspan': view.$el.data('offset'),
                'class': 'select-row-cell grid-cell grid-body-cell'
            }))
        }

        return view
    }
});

export default ShoppingListRow;
