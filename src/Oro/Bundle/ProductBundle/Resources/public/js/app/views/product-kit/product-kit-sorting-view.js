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
                element: function($item) {
                    const placeholder = document.createElement('div');
                    placeholder.classList.add('product-kit-item__placeholder');
                    placeholder.innerText = __('oro.product.product_kit.drop_placeholder_label');
                    placeholder.style.height = $item.height();
                    return placeholder;
                },
                update: function({currentItem}, placeholder) {
                    placeholder.css('height', currentItem.height());
                }
            },
            classes: {
                'ui-sortable-helper': 'product-kit-item__sortable-helper'
            },
            beforePick: this.beforePick.bind(this),
            stop: this.onStop.bind(this)
        });
    },

    beforePick({currentTarget}, {item}) {
        item.addClass('product-kit-item__sortable-helper');
    },

    onStop() {
        this.$(this.sortOrderFieldSelector).each((index, element) => element.value = index + 1);
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
