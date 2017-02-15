define(function(require) {
    'use strict';

    var BaseProductPricesView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var layout = require('oroui/js/layout');
    var BaseModel = require('oroui/js/app/models/base/model');
    var PricesHelper = require('oropricing/js/app/prices-helper');
    var _ = require('underscore');
    var $ = require('jquery');

    BaseProductPricesView = BaseView.extend(_.extend({}, ElementsHelper, {
        options: {
            defaultQuantity: 1
        },

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
            price: 0
        },

        modelEvents: {
            'prices setPrices': ['change', 'setPrices'],
            'quantity setFoundPrice': ['change', 'onQuantityChange'],
            'unit setFoundPrice': ['change', 'onUnitChange'],
            'price updateUI': ['change', 'updateUI']
        },

        prices: null,

        foundPrice: null,

        changeQuantity: false,

        rendered: false,

        initialize: function(options) {
            BaseProductPricesView.__super__.initialize.apply(this, arguments);

            $.extend(this, _.pick(options, ['changeQuantity']));

            this.deferredInitializeCheck(options, ['productModel']);
        },

        deferredInitialize: function(options) {
            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);
            this.render();
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
            if (!options.productModel) {
                this.$el.trigger('options:set:productModel', options);
            }
            if (options.productModel) {
                this.model = options.productModel;
            }

            if (!this.model) {
                this.model = new BaseModel();
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
            if (!content.length) {
                return;
            }

            if (!$pricesHint.data('popover')) {
                layout.initPopoverForElements($pricesHint, {
                    container: 'body'
                }, true);
            }

            $pricesHint.data('popover').updateContent(content);
        },

        getHintContent: function() {
            return this.getElement('pricesHintContent').html() || '';
        },

        setPrices: function() {
            this.prices = PricesHelper.preparePrices(this.model.get('prices'));
            this.foundPrice = {};

            if (!this.rendered) {
                this.rendered = true;
                this.setFoundPrice(true);
            } else {
                this.setFoundPrice();
            }
        },

        onQuantityChange: function(options) {
            if (options.manually) {
                this.changeQuantity = false;
            }
            this.setFoundPrice();
        },

        onUnitChange: function(options) {
            this.setFoundPrice(options.manually || false);
        },

        setFoundPrice: function(changeQuantity) {
            this.setPriceValue(this.findPrice(changeQuantity));
        },

        setPriceValue: function(price) {
            this.model.set('price', price);
        },

        findPrice: function(changeQuantity) {
            var quantity = this.model.get('quantity');
            var unit = this.model.get('unit');
            changeQuantity = changeQuantity && this.changeQuantity;

            var foundKey = unit + ' ' + quantity + ' ' + (changeQuantity ? 1 : 0);
            var price = this.foundPrice[foundKey] || null;

            if (!price) {
                if (changeQuantity) {
                    price = _.last(this.prices[unit]) || null;//sorted by quantity, get smallest
                } else {
                    price = PricesHelper.findPrice(this.prices, unit, quantity);
                }

                this.foundPrice[foundKey] = price;
            }

            if (changeQuantity) {
                var setQuantity = price ? price.quantity : this.options.defaultQuantity;
                if (quantity.toString() !== setQuantity.toString()) {
                    this.model.set('quantity', setQuantity);
                }
            }

            return price;
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
                this.getElement('unit').text(price.formatted_unit);

                this.getElement('priceValue').text(price.formatted_price);

                this.getElement('priceNotFound').addClass('hidden');
                this.getElement('price').removeClass('hidden');
            }
            this.renderHint();
        }
    }));

    return BaseProductPricesView;
});
