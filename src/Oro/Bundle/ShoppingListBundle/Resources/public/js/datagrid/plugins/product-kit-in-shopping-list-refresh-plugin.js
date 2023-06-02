import mediator from 'oroui/js/mediator';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';

const ProductKitInShoppingListRefreshPlugin = BasePlugin.extend({
    constructor: function ProductKitInShoppingListRefreshPlugin(...args) {
        ProductKitInShoppingListRefreshPlugin.__super__.constructor.apply(this, args);
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
