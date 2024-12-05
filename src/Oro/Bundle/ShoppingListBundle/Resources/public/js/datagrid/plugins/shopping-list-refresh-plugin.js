import mediator from 'oroui/js/mediator';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import __ from 'orotranslation/js/translator';
import PageableCollection from 'orodatagrid/js/pageable-collection';

const ShoppingListRefreshPlugin = BasePlugin.extend({
    constructor: function ShoppingListRefreshPlugin(grid, options) {
        ShoppingListRefreshPlugin.__super__.constructor.call(this, grid, options);
    },

    enable: function() {
        if (this.enabled) {
            return;
        }

        if (this.main.collection.options.hiddenLineItems) {
            this.messageHiddenLineItem(this.main.collection.options.hiddenLineItems);
        }

        this.main.collection.on('beforeReset', (collection, models, options) => {
            const {hiddenLineItems = {}} = options;
            this.messageHiddenLineItem(hiddenLineItems);
        });

        let storeCurrentPage;

        this.listenTo(this.main.collection, 'beforeFetch', collection => {
            storeCurrentPage = collection.previousState.currentPage;
        });

        this.listenTo(this.main.collection, 'request', collection => {
            if (collection instanceof PageableCollection && storeCurrentPage === collection.state.currentPage) {
                mediator.trigger('shopping-list:request');
            }
        });

        this.listenTo(this.main.collection, 'reset', collection => {
            if (collection instanceof PageableCollection && storeCurrentPage === collection.state.currentPage) {
                mediator.trigger('shopping-list:refresh');
            }
        });

        ShoppingListRefreshPlugin.__super__.enable.call(this);
    },

    messageHiddenLineItem: function(hiddenLineItems) {
        if (Array.isArray(hiddenLineItems) && hiddenLineItems.length > 0) {
            mediator.execute(
                'showFlashMessage',
                'warning',
                __('oro.frontend.shoppinglist.messages.line_items_not_available', {skus: hiddenLineItems.join(', ')}),
                {}
            );
        }
    }
});

export default ShoppingListRefreshPlugin;
