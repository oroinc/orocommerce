define(function(require) {
    'use strict';

    var LineItemProductPricesView;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var ProductPricesEditableView = require('oropricing/js/app/views/product-prices-editable-view');

    LineItemProductPricesView = ProductPricesEditableView.extend({
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
         * @inheritDoc
         */
        constructor: function LineItemProductPricesView() {
            LineItemProductPricesView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        deferredInitialize: function(options) {
            LineItemProductPricesView.__super__.deferredInitialize.apply(this, arguments);

            if (!this.options.editable) {
                this.getElement('priceValue').prop('readonly', true);
                var productId = this.model.get('id');
                if (!_.isUndefined(productId) && productId.length && this.model.get('price')) {
                    // store current values
                    this.storedValues = _.extend({}, this.model.attributes);
                }
            }

            mediator.on('pricing:collect:line-items', this.collectLineItems, this);
            mediator.on('pricing:refresh:products-tier-prices', this.refreshTierPrices, this);

            mediator.trigger('pricing:get:products-tier-prices', _.bind(function(tierPrices) {
                var productId = this.model.get('id');

                if (!_.isUndefined(productId) && _.isUndefined(tierPrices[productId])) {
                    // load prices from server for new line items
                    this.updateTierPrices();
                }

                this.setTierPrices(tierPrices, false);
            }, this));
        },

        updateTierPrices: function() {
            var productId = this.model.get('id');
            if (productId.length === 0) {
                this.refreshTierPrices({});
            } else {
                mediator.trigger(
                    'pricing:load:products-tier-prices',
                    [productId],
                    _.bind(this.refreshTierPrices, this)
                );
            }
        },

        /**
         * @param {Object} tierPrices
         * @param {Boolean} silent
         */
        setTierPrices: function(tierPrices, silent) {
            this.tierPrices = tierPrices;
            var prices = {};

            var productId = this.model.get('id');
            if (!_.isUndefined(productId) && productId.length !== 0) {
                prices = tierPrices[productId] || {};
            }

            var currency = this.model.get('currency');
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
            var productId = this.model.get('id');
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
            var productId = this.model.get('id');

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
            var productId = this.model.get('id');
            var prices = {};
            if (!_.isUndefined(productId) && productId.length !== 0) {
                prices = this.tierPrices[productId] || {};
            }
            var currencies = [];
            var units = [];

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
                var price;
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

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);

            delete this.storedValues;

            LineItemProductPricesView.__super__.dispose.call(this);
        }
    });

    return LineItemProductPricesView;
});
