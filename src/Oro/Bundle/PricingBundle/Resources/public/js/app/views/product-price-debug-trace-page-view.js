import BaseView from 'oroui/js/app/views/base/view';

const ProductPriceDebugTracePageView = BaseView.extend({
    events: {
        'click .price-details-item': 'onPriceItemClick'
    },

    highlightedClass: 'highlighted',

    constructor: function ProductPriceDebugTracePageView(...args) {
        ProductPriceDebugTracePageView.__super__.constructor.apply(this, args);
    },

    onPriceItemClick(event) {
        const $target = this.$(event.currentTarget);
        const priceId = $target.data('price-id');
        const isHighlighted = $target.hasClass(this.highlightedClass);

        if (priceId) {
            this.$('[data-price-id]').removeClass(this.highlightedClass);
            if (!isHighlighted) {
                this.$(`[data-price-id="${priceId}"]`).addClass(this.highlightedClass);
            }
        }
    }
});

export default ProductPriceDebugTracePageView;
