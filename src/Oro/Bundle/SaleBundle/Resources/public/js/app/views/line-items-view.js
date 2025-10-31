import $ from 'jquery';
import QuoteProductsPricesComponent from 'orosale/js/app/components/quote-products-prices-component';
import BaseView from 'oroui/js/app/views/base/view';

/**
 * @export orosale/js/app/views/line-items-view
 * @extends oroui.app.views.base.View
 * @class orosale.app.views.LineItemsView
 */
const LineItemsView = BaseView.extend({
    /**
     * @inheritDoc
     */
    options: {
        tierPrices: null
    },

    constructor: function LineItemsView(options) {
        LineItemsView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = $.extend(true, {}, this.options, options || {});

        this.subview('productsPricesComponent', new QuoteProductsPricesComponent({
            tierPrices: this.options.tierPrices
        }));

        this.initLayout();
    }
});

export default LineItemsView;
