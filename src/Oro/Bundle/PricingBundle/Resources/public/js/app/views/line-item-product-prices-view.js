define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const ProductPricesEditableView = require('oropricing/js/app/views/product-prices-editable-view');

    const LineItemProductPricesView = ProductPricesEditableView.extend({
        elements: _.extend({}, ProductPricesEditableView.prototype.elements, {
            currency: '[data-name="field__currency"]'
        }),

        modelElements: _.extend({}, ProductPricesEditableView.prototype.modelElements, {
            currency: 'currency'
        }),

        modelEvents: _.extend({}, ProductPricesEditableView.prototype.modelEvents, {
            'id updateTierPrices': ['change', 'updateTierPrices'],
            'currency updatePriceValue': ['change', 'updatePriceValue'],
            'unit updatePriceValue': ['change', 'updatePriceValue'],
            'quantity updatePriceValue': ['change', 'updatePriceValue']
        }),

        storedValues: {},

        /**
         * @inheritdoc
         */
        constructor: function LineItemProductPricesView(options) {
            LineItemProductPricesView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        deferredInitialize: function(options) {
            LineItemProductPricesView.__super__.deferredInitialize.call(this, options);

            if (!this.options.editable) {
                this.getElement('priceValue').prop('readonly', true);
                const productId = this.model.get('id');
                if (!_.isUndefined(productId) && productId.length && this.model.get('price')) {
                    // store current values
                    this.storedValues = _.extend({}, this.model.attributes);
                }
            }

            this.listenTo(mediator, {
                'pricing:collect:line-items': this.collectLineItems,
                'pricing:refresh:products-tier-prices': this.refreshTierPrices,
                'pricing:currency:changed': this.currencyChanged
            });

            mediator.trigger('pricing:currency:load', this.currencyChanged.bind(this));
            mediator.trigger('pricing:get:products-tier-prices', tierPrices => {
                const productId = this.model.get('id');

                if (!_.isUndefined(productId) && _.isUndefined(tierPrices[productId])) {
                    // load prices from server for new line items
                    this.updateTierPrices();
                }

                this.setTierPrices(tierPrices, false);
            });
        },

        updateTierPrices: function() {
            const productId = this.model.get('id');
            if (productId.length === 0) {
                this.refreshTierPrices({});
            } else {
                mediator.trigger(
                    'pricing:load:products-tier-prices',
                    [productId],
                    this.refreshTierPrices.bind(this)
                );
            }
        },

        /**
         * @param {Object} tierPrices
         * @param {Boolean} silent
         */
        setTierPrices: function(tierPrices, silent) {
            this.tierPrices = tierPrices;
            let prices = {};

            const productId = this.model.get('id');
            if (!_.isUndefined(productId) && productId.length !== 0) {
                prices = tierPrices[productId] || {};
            }

            const currency = this.model.get('currency');
            if (currency) {
                prices = _.filter(prices, function(price) {
                    return price.currency === currency;
                });
            }

            this.model.set('prices', prices, {
                silent: silent || false
            });
        },

        /**
         * @param {Object} tierPrices
         */
        refreshTierPrices: function(tierPrices) {
            const productId = this.model.get('id');
            this.setTierPrices(tierPrices, false);
            if (!this.options.editable) {
                this.filterValues();
                if (productId) {
                    this.updatePriceValue();
                }
            }
        },

        /**
         * @param {Array} items
         */
        collectLineItems: function(items) {
            const productId = this.model.get('id');

            if (!_.isUndefined(productId) && productId.length) {
                items.push({
                    product: productId,
                    unit: this.model.get('unit'),
                    quantity: this.model.get('quantity'),
                    currency: this.model.get('currency')
                });
            }
        },

        filterValues: function() {
            const productId = this.model.get('id');
            let prices = {};
            if (!_.isUndefined(productId) && productId.length !== 0) {
                prices = this.tierPrices[productId] || {};
            }
            const currencies = [];
            const units = [];

            _.each(prices, function(price) {
                currencies.push(price.currency);
                units.push(price.unit);
            });
            if (!_.isUndefined(this.storedValues.price)) {
                currencies.push(this.storedValues.currency);
                units.push(this.storedValues.unit);
            } else if (_.isUndefined(productId) || productId.length === 0) {
                currencies.push(this.model.get('currency'));
                units.push(this.model.get('unit'));
            }

            // we always filter only initial list of currencies
            this.getElement('currency')
                .find('option')
                .filter(function() {
                    return (-1 === $.inArray(this.value, currencies));
                })
                .remove();

            this.model.trigger('product:unit:filter-values', units);
        },

        updatePriceValue: function() {
            this.setTierPrices(this.tierPrices);
            if (!this.options.editable) {
                let price;
                if (this.storedValues &&
                    this.model.get('id') === this.storedValues.id &&
                    this.model.get('unit') === this.storedValues.unit &&
                    this.model.get('quantity') === this.storedValues.quantity &&
                    this.model.get('currency') === this.storedValues.currency
                ) {
                    price = this.storedValues;
                } else {
                    price = this.findPrice();
                }
                this.setPriceValue(price ? price.price : null);
                this.getElement('priceValue').addClass('matched-price');
            }
        },

        currencyChanged: function(options) {
            if (options.currency) {
                this.model.set('currency', options.currency);
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.storedValues;

            LineItemProductPricesView.__super__.dispose.call(this);
        }
    });

    return LineItemProductPricesView;
});
