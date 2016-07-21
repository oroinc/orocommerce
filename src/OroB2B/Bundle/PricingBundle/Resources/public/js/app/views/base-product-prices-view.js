define(function(require) {
    'use strict';

    var BaseProductPricesView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var _ = require('underscore');
    var $ = require('jquery');
    var NumberFormatter = require('orolocale/js/formatter/number');

    BaseProductPricesView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            price: '[data-name="price"]',
            unit: '[data-name="unit"]',
            priceValue: '[data-name="price-value"]',
            priceNotFound: '[data-name="price-not-found"]',
            pricesHint: '[data-name="prices-hint"]',
            pricesHintContent: '[data-name="prices-hint-content"]'
        },

        modelAttr: {
            prices: {},
            quantityWasChanged: false
        },

        defaultQuantity: 1,
        prices: {},

        initialize: function(options) {
            BaseProductPricesView.__super__.initialize.apply(this, arguments);

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            this.setPrices(this.model.get('prices'));

            this.model.on('change:quantity', this.updatePriceWithoutQuantity, this);
            this.model.on('change:unit', this.updatePriceWithQuantity, this);

            this.render();
        },

        dispose: function() {
            delete this.modelAttr;
            this.disposeElements();
            this.model.off('change:quantity', this.updatePriceWithoutQuantity, this);
            this.model.off('change:unit', this.updatePriceWithQuantity, this);
            BaseProductPricesView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.updatePrice();
            this.renderHint();
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        renderHint: function() {
            var $pricesHint = this.getElement('pricesHint');

            if ($pricesHint.length) {
                var $pricesHintContent = this.getElement('pricesHintContent');
                $pricesHint.data('content', $pricesHintContent.html());
                $pricesHint.popover({
                    animation: false,
                    html: true
                });
            }
        },

        setPrices: function(prices) {
            this.prices = {};
            _.each(prices, function(price) {
                if (!this.prices[price.unit]) {
                    this.prices[price.unit] = [];
                }
                this.prices[price.unit].push(price);
            }, this);

            //sort for optimize findPrice
            _.each(this.prices, function(unitPrices, unit) {
                unitPrices = _.sortBy(unitPrices, 'quantity');
                unitPrices.reverse();
                this.prices[unit] = unitPrices;
            }, this);
        },

        updatePriceWithoutQuantity: function() {
            this.updatePrice(false);
            this.modelAttr.quantityWasChanged = true;
        },

        updatePriceWithQuantity: function() {
            this.updatePrice(true);
        },

        updatePrice: function(quantityUpdate) {
            quantityUpdate = typeof quantityUpdate !== 'undefined' ? quantityUpdate : true;

            var priceData = {
                quantity: this.model.get('quantity'),
                unit: this.model.get('unit')
            };

            this.renderPrice(this.findPrice(priceData, quantityUpdate));
        },

        findPrice: function(priceData, quantityUpdate) {

            if (!priceData || !_.isObject(priceData)) {
                return null;
            }

            var smallestPrice = null;
            var price =  _.find(this.prices[priceData.unit], function(price) {
                smallestPrice = price;
                return price.quantity <= priceData.quantity;
            }) || null;

            if (!this.modelAttr.quantityWasChanged && quantityUpdate) {
                this.model.set('quantity', smallestPrice ? smallestPrice.quantity : this.defaultQuantity);
                this.modelAttr.quantityWasChanged = false;
                price=smallestPrice;
            }

            return price;
        },

        renderPrice: function(price) {
            if (price === null) {
                this.getElement('price').addClass('hidden');
                this.getElement('priceNotFound').removeClass('hidden');
            } else {
                this.getElement('unit').html(this.model.get('product_units')[price.unit]);

                price = NumberFormatter.formatCurrency(price.price, price.currency);
                this.getElement('priceValue').html(price);

                this.getElement('priceNotFound').addClass('hidden');
                this.getElement('price').removeClass('hidden');
            }
        }
    }));

    return BaseProductPricesView;
});
