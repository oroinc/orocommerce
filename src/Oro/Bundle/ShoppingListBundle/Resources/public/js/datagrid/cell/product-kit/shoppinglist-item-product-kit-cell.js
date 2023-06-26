import ShoppingListItemCell from '../shoppinglist-item-cell';
import viewportManager from 'oroui/js/viewport-manager';
import mediator from 'oroui/js/mediator';

const ShoppingListItemProductKitCell = ShoppingListItemCell.extend({
    optionNames: ['column', 'themeOptions'],

    listen: {
        'viewport:change mediator': 'onViewportChange'
    },

    _attributes() {
        return {
            colspan: this.expandKitLineItem && !viewportManager.isApplicable('tablet') ? 2 : null
        };
    },

    constructor: function ShoppingListItemProductKitCell(...args) {
        ShoppingListItemProductKitCell.__super__.constructor.apply(this, args);
    },

    /**
     * @inheritdoc
     */
    preinitialize() {
        this.expandKitLineItem = this.themeOptions?.expandKitLineItem ?? true;
    },

    /**
     * @inheritdoc
     */
    initialize(...args) {
        ShoppingListItemProductKitCell.__super__.initialize.apply(this, args);

        if (this.expandKitLineItem) {
            this.listenTo(mediator, 'viewport:change', this.onViewportChange);
        }
    },

    onViewportChange(event) {
        this.$el.attr('colspan', !viewportManager.isApplicable('tablet') ? 2 : null);
    }
});

export default ShoppingListItemProductKitCell;
