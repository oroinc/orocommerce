import BaseView from 'oroui/js/app/views/base/view';
import __ from 'orotranslation/js/translator';
import 'jquery-ui/widgets/sortable';

const ProductKitSortingView = BaseView.extend({
    autoRender: true,

    sortOrderFieldSelector: '[name$="[sortOrder]"]',

    constructor: function ProductKitSortingView(...args) {
        ProductKitSortingView.__super__.constructor.apply(this, args);
    },

    render() {
        this.$el.sortable({
            tolerance: 'pointer',
            delay: 100,
            containment: 'parent',
            handle: '[data-name="sortable-handle"]',
            placeholder: {
                element: function() {
                    const placeholder = document.createElement('div');
                    placeholder.classList.add('product-kit-item__placeholder');
                    placeholder.innerText = __('oro.product.product_kit.drop_placeholder_label');
                    return placeholder;
                },
                update: function(container, p) {
                    return;
                }
            },
            classes: {
                'ui-sortable-helper': 'product-kit-item__sortable-helper'
            },
            beforePick: this.beforePick.bind(this),
            stop: this.onStop.bind(this)
        });
    },

    beforePick(event, {item}) {
        item.addClass('product-kit-item__sortable-helper');
    },

    onStop() {
        this.$(this.sortOrderFieldSelector).each((index, element) => element.value = index);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.$el.sortable('destroy');

        ProductKitSortingView.__super__.dispose.call(this);
    }
});

export default ProductKitSortingView;
