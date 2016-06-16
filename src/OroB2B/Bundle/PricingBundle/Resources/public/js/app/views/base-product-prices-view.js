define(function(require) {
    'use strict';

    var BaseProductPricesView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var _ = require('underscore');
    var $ = require('jquery');
    var NumberFormatter = require('orolocale/js/formatter/number');

    BaseProductPricesView = BaseView.extend(_.extend({}, ElementsHelper, {
        autoRender: true,

        elements: {
            price: '[data-name="price"]',
            priceValue: '[data-name="price-value"]',
            priceNotFound: '[data-name="price-not-found"]',
            pricesHint: '[data-name="prices-hint"]',
            pricesHintContent: '[data-name="prices-hint-content"]'
        },

        modelAttr: {
            prices: {},
            price: 0
        },

        modelEvents: {
            'prices setPrices': ['change', 'setPrices'],
            'quantity updatePrice': ['change', 'updatePrice'],
            'unit updatePrice': ['change', 'updatePrice'],
            'price updateUI': ['change', 'updateUI']
        },

        prices: null,

        foundPrice: null,

        initialize: function(options) {
            BaseProductPricesView.__super__.initialize.apply(this, arguments);

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);
        },

        dispose: function() {
            delete this.modelAttr;
            delete this.prices;
            delete this.foundPrice;
            this.disposeElements();
            BaseProductPricesView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.setPrices();
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
            if (!$pricesHint.length) {
                return;
            }

            var content = this.getHintContent();
            $pricesHint.toggleClass('disabled', content.length === 0);

            if (!$pricesHint.data('popover')) {
                $pricesHint.popover({
                    animation: false,
                    html: true,
                    container: 'body'
                });
            }

            $pricesHint.data('popover').updateContent(content);
        },

        getHintContent: function() {
            return this.getElement('pricesHintContent').html();
        },

        setPrices: function() {
            this.prices = {};
            this.foundPrice = {};

            _.each(this.model.get('prices'), function(price) {
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

            this.updatePrice();
        },

        updatePrice: function() {
            this.model.set('price', this.findPrice());
        },

        findPrice: function() {
            var quantity = this.model.get('quantity');
            var unit = this.model.get('unit');
            var foundKey = unit + ' ' + quantity;

            if (!this.foundPrice[foundKey]) {
                this.foundPrice[foundKey] = _.find(this.prices[unit], function(price) {
                    return price.quantity <= quantity;
                }) || null;
            }
            return this.foundPrice[foundKey];
        },

        findPriceValue: function() {
            var price = this.findPrice();
            return price ? price.price : null;
        },

        updateUI: function() {
            var price = this.model.get('price');
            if (price === null) {
                this.getElement('price').addClass('hidden');
                this.getElement('priceNotFound').removeClass('hidden');
            } else {
                price = NumberFormatter.formatCurrency(price.price, price.currency);
                this.getElement('priceValue').html(price);

                this.getElement('priceNotFound').addClass('hidden');
                this.getElement('price').removeClass('hidden');
            }
            this.renderHint();
        }
    }));

    return BaseProductPricesView;
});
