import _ from 'underscore';
import tools from 'oroui/js/tools';
import routing from 'routing';
import BaseComponent from 'oroui/js/app/components/base/component';
import localeSettings from 'orolocale/js/locale-settings';
import errorHandler from 'oroui/js/error';
import ListItemProductPricesView from 'oropricing/js/app/views/list-item-product-prices-view';

const ListItemProductPricesComponent = BaseComponent.extend({
    /**
     * @type {string}
     */
    tierPricesRoute: 'oro_pricing_frontend_price_by_customer',

    /**
     * @type {string|number}
     */
    productId: void 0,

    /**
     * @type {Object}
     */
    viewOptions: null,

    /**
     * @inheritdoc
     */
    constructor: function ListItemProductPricesComponent(options) {
        ListItemProductPricesComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        Object.assign(this, _.pick(options, 'tierPricesRoute', 'productId'));

        this.viewOptions = {
            el: options._sourceElement,
            ..._.pick(options, 'showValuePrice', 'showListedPrice', 'doUpdateQtyForUnit', 'elements')
        };

        this.loadPrices();
    },

    loadPrices: function() {
        this.viewOptions.el.addClass('loader-in-process');

        const params = {
            currency: localeSettings.getCurrency(),
            product_ids: [this.productId]
        };
        const URL = routing.generate(this.tierPricesRoute, params);

        fetch(URL)
            .then(response => response.json())
            .then(data => this.onPricesLoad(data[this.productId] || {}))
            .catch(() => errorHandler.showErrorInConsole(
                new Error(`Unable to load prices for ${this.productId} product`)))
            .finally(() => {
                this.viewOptions.el.removeClass('loader-in-process');

                if (tools.isIE11()) {
                    // Force icon repaint
                    this.viewOptions.el.find('.fa--loader-icon').css('display');
                }
            });
    },

    onPricesLoad: function(prices) {
        this.view = new ListItemProductPricesView({
            ...this.viewOptions,
            modelAttr: {prices}
        });
        this.view.getElement('pricesHint').click();
    }
});

export default ListItemProductPricesComponent;
