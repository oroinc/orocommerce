define(function(require) {
    'use strict';

    var LineItemProductPricesView;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var NumberFormatter = require('orolocale/js/formatter/number');
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

        options: _.extend({}, ProductPricesEditableView.prototype.options, {
            allowPriceEdit: false
        }),

        storedValues: {},

        /**
         * @inheritDoc
         */
        deferredInitialize: function(options) {
            LineItemProductPricesView.__super__.deferredInitialize.apply(this, arguments);

            mediator.on('pricing:collect:line-items', this.collectLineItems, this);
            mediator.on('pricing:refresh:products-tier-prices', this.refreshTierPrices, this);

            mediator.trigger('pricing:get:products-tier-prices', _.bind(function(tierPrices) {
                this.setTierPrices(tierPrices, false);
            }, this));

            if (!this.options.allowPriceEdit) {
                this.getElement('priceValue').prop('disabled', true);
                var productId = this.model.get('id');
                if (!_.isUndefined(productId) && productId.length && this.model.get('price')) {
                    // store current values
                    this.storedValues = _.extend({}, this.model.attributes);
                }
            }
            this.updateTierPrices();
        },

        updateTierPrices: function() {
            var productId = this.model.get('id');
            if (productId.length === 0) {
                this.onUpdatePrices({});
            } else {
                mediator.trigger(
                    'pricing:load:products-tier-prices',
                    [productId],
                    _.bind(this.onUpdatePrices, this)
                );
            }
        },

        changePricesCurrency: function() {
            this.setTierPrices(this.tierPrices);
            var price = this.findPrice();
            if (!price &&
                this.storedValues &&
                this.model.get('id') === this.storedValues.id &&
                this.model.get('unit') === this.storedValues.unit &&
                this.model.get('quantity') === this.storedValues.quantity &&
                this.model.get('currency') === this.storedValues.currency
            ) {
                price = this.storedValues;
            }
            this.setPriceValue(price ? price.price : null);
            this.getElement('priceValue').addClass('matched-price');
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
         * @param {Boolean} silent
         */
        refreshTierPrices: function(tierPrices, silent) {
            var productId = this.model.get('id');
            if (productId && !_.isUndefined(tierPrices) && _.isUndefined(tierPrices[productId])) {
                return;
            }
            this.setTierPrices(tierPrices, false);
            if (!this.options.allowPriceEdit) {
                this.filterValues(true);
            }
            this.setTierPrices(tierPrices, false);
        },

        /**
         * @param {Object} tierPrices
         */
        onUpdatePrices: function(tierPrices) {
            var productId = this.model.get('id');
            if (productId && !_.isUndefined(tierPrices) && _.isUndefined(tierPrices[productId])) {
                return;
            }
            this.setTierPrices(tierPrices, false);
            if (!this.options.allowPriceEdit) {
                this.filterValues(!this.model.get('price'));
                if (!this.model.get('price')) {
                    this.findPrice();
                    this.setPriceValue(this.findPriceValue());
                    this.updateUI();
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

        filterValues: function(keepSelected) {
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
            var price = this.findPrice();
            if (!price &&
                this.storedValues &&
                this.model.get('id') === this.storedValues.id &&
                this.model.get('unit') === this.storedValues.unit &&
                this.model.get('quantity') === this.storedValues.quantity &&
                this.model.get('currency') === this.storedValues.currency
            ) {
                price = this.storedValues;
            }
            this.setPriceValue(price ? price.price : null);
            this.getElement('priceValue').addClass('matched-price');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);

            LineItemProductPricesView.__super__.dispose.call(this);
        }
    });

    return LineItemProductPricesView;
});
