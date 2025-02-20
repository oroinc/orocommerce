import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import BaseComponent from 'oroui/js/app/components/base/component';

const QuoteProductsPricesComponent = BaseComponent.extend({
    /**
     * @inheritDoc
     */
    options: {
        tierPrices: null
    },

    constructor: function QuoteProductsPricesComponent(options) {
        QuoteProductsPricesComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize: function(options) {
        QuoteProductsPricesComponent.__super__.initialize.call(this, options);

        this.options = $.extend(true, {}, this.options, options || {});

        mediator.trigger('pricing:refresh:products-tier-prices', this.options.tierPrices);

        this.listenTo(mediator, {
            'pricing:get:products-tier-prices': this.getProductsTierPrices,
            'pricing:load:products-tier-prices': this.loadProductsTierPrices,
            'pricing:load:prices': this.reloadPrices,
            'entry-point:quote:load': this.onQuoteEntryPointLoad
        });
    },

    /*
     * @param {Function} callback
     */
    getProductsTierPrices: function(callback) {
        callback(this.options.tierPrices);
    },

    /**
     * @param {Array} products
     * @param {Function} callback
     */
    loadProductsTierPrices: function(products, callback) {
        mediator.once('entry-point:quote:load', response => {
            callback(response.tierPrices || {});
        });

        mediator.trigger('entry-point:quote:trigger');
    },

    reloadPrices: function() {
        mediator.trigger('entry-point:quote:trigger');
    },

    onQuoteEntryPointLoad: function(response) {
        mediator.trigger('entry-point:quote:load:response', response || {});
        mediator.trigger('pricing:refresh:products-tier-prices', response.tierPrices || {});
    }
});

export default QuoteProductsPricesComponent;
