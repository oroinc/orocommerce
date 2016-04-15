define(function(require) {
    'use strict';

    var BaseProductPricesView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var NumberFormatter = require('orolocale/js/formatter/number');

    BaseProductPricesView = BaseView.extend(_.extend({}, ElementsHelper, {
        options: {
            elements: {
                quantity: '[data-role="field-quantity"]',
                unit: '[data-role="field-unit"]',
                price: '[data-role="price"]',
                priceValue: '[data-role="price-value"]',
                priceNotFound: '[data-role="price-not-found"]',
                pricesHint: '[data-role="prices-hint"]',
                pricesHintContent: '[data-role="prices-hint-content"]'
            },
            prices: {}
        },

        prices: {},

        initialize: function(options) {
            this.initializeElements(options);
            this.setPrices(options.prices || {});

            this.getElement('quantity').on('change', _.bind(this.updatePrice, this));
            this.getElement('unit').on('change', _.bind(this.updatePrice, this));

            this.render();
        },

        render: function() {
            this.updatePrice();
            this.renderHint();
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
            _.each(prices, function(unitPrices) {
                _.sortBy(unitPrices, 'qty');
                unitPrices.reverse();
            });
            this.prices = prices;
        },

        updatePrice: function() {
            var priceData = null;
            if (this.validate()) {
                priceData = {
                    quantity: this.getElement('quantity').val(),
                    unit: this.getElement('unit').val()
                };
            }

            this.renderPrice(this.findPrice(priceData));
        },

        findPrice: function(priceData) {
            if (!priceData || !_.isObject(priceData)) {
                return null;
            }

            return _.find(this.prices[priceData.unit], function(price) {
                return price.qty <= priceData.quantity;
            }) || null;
        },

        renderPrice: function(price) {
            if (price === null) {
                this.getElement('price').hide();
                this.getElement('priceNotFound').show();
            } else {
                price = NumberFormatter.formatCurrency(price.price, price.currency);
                this.getElement('priceValue').html(price);

                this.getElement('priceNotFound').hide();
                this.getElement('price').show();
            }
        },

        validate: function() {
            var validator = this.getElement('quantity').closest('form').validate();
            return !validator || validator.form();
        }
    }));

    return BaseProductPricesView;
});
