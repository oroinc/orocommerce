import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import BaseComponent from 'oroui/js/app/components/base/component';

const ProductsPricesComponent = BaseComponent.extend({
    /**
     * @inheritDoc
     */
    options: {
        tierPrices: null
    },

    constructor: function ProductsPricesComponent(options) {
        ProductsPricesComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize: function(options) {
        ProductsPricesComponent.__super__.initialize.call(this, options);

        this.options = $.extend(true, {}, this.options, options || {});

        mediator.trigger('pricing:refresh:products-tier-prices', this.options.tierPrices);

        this.listenTo(mediator, {
            'pricing:get:products-tier-prices': this.getProductsTierPrices,
            'pricing:load:products-tier-prices': this.loadProductsTierPrices,
            'pricing:load:prices': this.reloadPrices,
            'entry-point:order:load': this.onOrderEntryPointLoad
        });
    },

    /*
     * @param {Function} callback
     */
    getProductsTierPrices: function(callback) {
        callback(this.options.tierPrices);
    },

    loadProductsTierPrices: function(products, callback) {
        mediator.once('entry-point:order:load', response => {
            callback(response.tierPrices || {});
        });

        mediator.trigger('entry-point:order:trigger');
    },

    reloadPrices: function() {
        mediator.trigger('entry-point:order:trigger');
    },

    onOrderEntryPointLoad: function(response) {
        mediator.trigger('pricing:refresh:products-tier-prices', response.tierPrices || {});
    }
});

export default ProductsPricesComponent;
