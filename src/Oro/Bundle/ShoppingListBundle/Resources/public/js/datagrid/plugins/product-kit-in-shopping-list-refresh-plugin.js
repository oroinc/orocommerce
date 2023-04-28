import mediator from 'oroui/js/mediator';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';

const ProductKitInShoppingListRefreshPlugin = BasePlugin.extend({
    constructor: function ShoppingListRefreshPlugin(grid, options) {
        ShoppingListRefreshPlugin.__super__.constructor.call(this, grid, options);
    },

    enable: function() {
        if (this.enabled) {
            return;
        }

        this.listenTo(mediator, 'shopping-list:line-items:update-response', () => {
            mediator.trigger(`datagrid:doRefresh:${this.main.name}`);
        });

        ProductKitInShoppingListRefreshPlugin.__super__.enable.call(this);
    }
});

export default ProductKitInShoppingListRefreshPlugin;
