import ShoppingListItemCell from '../shoppinglist-item-cell';
import viewportManager from 'oroui/js/viewport-manager';
import mediator from 'oroui/js/mediator';

const ShoppingListItemProductKitCell = ShoppingListItemCell.extend({
    constructor: function ShoppingListItemProductKitCell(...args) {
        ShoppingListItemProductKitCell.__super__.constructor.apply(this, args);
    },

    initialize(...args) {
        ShoppingListItemProductKitCell.__super__.initialize.apply(this, args);

        this.listenTo(mediator, 'viewport:change', this.onViewportChange);
    },

    listen: {
        'viewport:change mediator': 'onViewportChange'
    },

    _attributes() {
        return {
            colspan: !viewportManager.isApplicable('tablet') ? 2 : null
        };
    },

    onViewportChange(event) {
        this.$el.attr('colspan', !viewportManager.isApplicable('tablet') ? 2 : null);
    }
});

export default ShoppingListItemProductKitCell;
