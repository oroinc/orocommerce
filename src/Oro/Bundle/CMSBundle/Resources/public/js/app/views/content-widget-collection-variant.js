import {debounce} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';

const ContentWidgetCollectionVariantView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'itemContent', 'itemSelector', 'itemRowSelector',
        'itemLabelSelector', 'itemLabelSourceSelector'
    ]),

    itemSelector: '[data-role="content-variant-item"]',

    itemContent: '[name$="[content]"]',

    itemRowSelector: '[data-role="content-variant-item-row"]',

    itemLabelSelector: '[data-role="content-variant-item-title"]',

    itemLabelSourceSelector: '[name$="[title]"]',

    events() {
        return {
            [`shown.bs.collapse ${this.itemSelector}`]: 'handleToggleCollapse',
            [`hide.bs.collapse ${this.itemSelector}`]: 'handleToggleCollapse',
            [`change ${this.itemLabelSourceSelector}`]: 'updateItemLabel',
            'row-collection:added': 'onCollectionRowAddedHandler'
        };
    },

    constructor: function ContentWidgetCollectionVariantView(...args) {
        this.onCollectionRowAddedHandler = debounce(this.onCollectionRowAddedHandler);
        this.scrollToBottom = debounce(this.scrollToBottom);
        ContentWidgetCollectionVariantView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        const showedItems = this.$(this.itemSelector).filter('.show');

        if (showedItems.length > 0) {
            showedItems.each((i, element) => this.toggleEditor(this.$(element), true));
        }

        ContentWidgetCollectionVariantView.__super__.initialize.call(this, options);
    },

    onCollectionRowAddedHandler(event, {item, method}) {
        if (item.length) {
            this.$(this.itemSelector).addClass('no-transition');
            item.find(this.itemSelector).collapse('show');
            this.toggleEditor(item, true);

            if (method === 'append') {
                this.scrollToBottom();
            }

            this.$(this.itemSelector).removeClass('no-transition');
        }
    },

    handleToggleCollapse(event) {
        if (event.namespace !== 'bs.collapse') {
            return;
        }

        const target = this.$(event.currentTarget);
        this.toggleEditor(target, event.type !== 'hide');
    },

    scrollToBottom() {
        this.$el.scrollParent().scrollTop(this.$el.scrollParent().get(0).scrollHeight);
    },

    toggleEditor(target, enable) {
        target.find(this.itemContent).trigger(enable ? 'wysiwyg:enable' : 'wysiwyg:disable');
    },

    updateItemLabel(event) {
        const target = this.$(event.currentTarget);
        target
            .closest(this.itemRowSelector)
            .find(this.itemLabelSelector)
            .text(target.val());
    }
});

export default ContentWidgetCollectionVariantView;
