import $ from 'jquery';
import _ from 'underscore';
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
    events: {
        mouseover: 'renderHint'
    },

    /**
     * url for image
     */
    src: null,

    /**
     * title for image
     */
    title: null,

    /**
     * @inheritDoc
     */
    initialize: function(options) {
        Object.assign(this, _.pick(options, 'src', 'title'));
        ShoppingListImagePopoverView.__super__.initialize.call(this, options);
    },

    renderHint(event) {
        if (!this.$el.data(Popover.DATA_KEY)) {
            layout.initPopoverForElements(this.$el, {
                'container': 'body',
                'placement': 'top',
                'close': false,
                'class': 'popover--no-title',
                'forceToShowTitle': true,
                'delay': {
                    show: 300,
                    hide: 0
                },
                'trigger': 'hover',
                'offset': '0, 1',
                'boundary': 'viewport'
            }, true);

            $(event.target).trigger(event);

            // Disable popover opening by click
            this.$el.off('click' + Popover.EVENT_KEY);
        }

        this.$el.data(Popover.DATA_KEY).updateContent(template({
            src: this.src,
            title: this.title
        }));
    }
});

export default ShoppingListImagePopoverView;
