import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import layout from 'oroui/js/layout';
import Popover from 'bootstrap-popover';
import template from 'tpl-loader!oroshoppinglist/templates/shoppinglist-image-popover.html';

const ShoppingListImagePopoverView = BaseView.extend({
    /**
     * @inheritdoc
     */
    autoRender: true,

    /**
     * url for image
     */
    src: null,

    /**
     * title for image
     */
    title: null,

    /**
     * Sources array that can be used in <picture> tag of popover image
     */
    popover_image_sources: [],

    /**
     * @inheritdoc
     */
    constructor: function ShoppingListImagePopoverView(options) {
        const {_initEvent: initEvent, ...restOptions} = options;
        ShoppingListImagePopoverView.__super__.constructor.call(this, restOptions);
        if (initEvent) {
            this.$el.popover('show');
        }
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        Object.assign(this, _.pick(options, 'src', 'title', 'popover_image_sources'));
        ShoppingListImagePopoverView.__super__.initialize.call(this, options);
    },

    render() {
        layout.initPopoverForElements(this.$el, {
            'forCID': this.cid,
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
            'offset': '0, 2',
            'content': template({
                src: this.src,
                title: this.title,
                popover_image_sources: this.popover_image_sources
            })
        }, true);

        // Make it possible to follow the link
        this.$el.off(`click${Popover.EVENT_KEY}`);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        if (this.$el.data(Popover.DATA_KEY)) {
            this.$el.popover('dispose');
        }

        return ShoppingListImagePopoverView.__super__.dispose.call(this);
    }
});

export default ShoppingListImagePopoverView;
