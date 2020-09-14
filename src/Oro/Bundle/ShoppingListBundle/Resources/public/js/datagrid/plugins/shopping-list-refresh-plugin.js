import mediator from 'oroui/js/mediator';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';

const ShoppingListRefreshPlugin = BasePlugin.extend({
    constructor: function ShoppingListRefreshPlugin(grid, options) {
        ShoppingListRefreshPlugin.__super__.constructor.call(this, grid, options);
    },

    enable: function() {
        if (this.enabled) {
            return;
        }

        this.listenTo(this.main.collection, 'request', () => mediator.trigger('shopping-list:request'));
        this.listenTo(this.main.collection, 'reset', () => mediator.trigger('shopping-list:refresh'));
        ShoppingListRefreshPlugin.__super__.enable.call(this);
    }
});

export default ShoppingListRefreshPlugin;
