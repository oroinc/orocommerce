import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import layout from 'oroui/js/layout';
import Popover from 'bootstrap-popover';
import template from 'tpl-loader!oroshoppinglist/templates/shoppinglist-image-popover.html';

const ShoppingListImagePopoverView = BaseView.extend({
    /**
     * @inheritDoc
     */
    constructor: function ShoppingListImagePopoverView(options) {
        ShoppingListImagePopoverView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize: function(options) {
        ShoppingListImagePopoverView.__super__.initialize.call(this, options);

        this.render();
    },

    /**
     * @inheritDoc
     */
    render() {
        this.renderHint();

        return this;
    },

    renderHint() {
        if (!this.$el.data(Popover.DATA_KEY)) {
            layout.initPopoverForElements(this.$el, {
                'container': 'body',
                'placement': 'top',
                'trigger': 'manual',
                'close': false,
                'class': 'popover--no-title',
                'forceToShowTitle': true
            }, true);

            // Disable popover opening by click
            this.$el.off('click' + Popover.EVENT_KEY);
            this.$el.on('mouseover' + Popover.EVENT_KEY, function() {
                $(this).popover('show');
            });
            this.$el.on('mouseout' + Popover.EVENT_KEY, function() {
                $(this).popover('hide');
            });
        }

        this.$el.data(Popover.DATA_KEY).updateContent(template({
            src: this.$el.data('popover-image'),
            title: this.$el.prop('title')
        }));
    }
});

export default ShoppingListImagePopoverView;
