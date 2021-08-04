define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const layout = require('oroui/js/layout');
    const mediator = require('oroui/js/mediator');
    const numberFormatter = require('orolocale/js/formatter/number');
    const numeral = require('numeral');
    const localeSettings = require('orolocale/js/locale-settings');
    const BaseModel = require('oroui/js/app/models/base/model');
    const PricesHelper = require('oropricing/js/app/prices-helper');
    const Popover = require('bootstrap-popover');
    const _ = require('underscore');
    const $ = require('jquery');

    const BaseProductPricesView = BaseView.extend(_.extend({}, ElementsHelper, {
        priceTemplate: require('tpl-loader!oropricing/templates/product/price.html'),
        unitTemplate: require('tpl-loader!oropricing/templates/product/unit.html'),

        keepElement: true,

        optionNames: BaseView.prototype.optionNames.concat([
            'changeQuantity', 'changeUnitLabel'
        ]),

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

        changeUnitLabel: false,

        rendered: false,

        /**
         * @inheritdoc
         */
        constructor: function BaseProductPricesView(options) {
            BaseProductPricesView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            BaseProductPricesView.__super__.initialize.call(this, options);
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
            this.setUnitLabel(true);
            this.disposeElements();
            BaseProductPricesView.__super__.dispose.call(this);
        },

        render: function() {
            this.setPrices();
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }

            if (!this.model) {
                this.model = new BaseModel();
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute) || !_.isEmpty(value)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        renderHint: function() {
            const $pricesHint = this.getElement('pricesHint');
            if (!$pricesHint.length) {
                return;
            }

            const content = this.getHintContent();
            $pricesHint
                .toggleClass('disabled', content.length === 0)
                .attr('disabled', content.length === 0);
            if (!content.length) {
                return;
            }

            if (!$pricesHint.data(Popover.DATA_KEY)) {
                layout.initPopoverForElements($pricesHint, {
                    container: 'body',
                    forceToShowTitle: true
                }, true);
            }

            $pricesHint.data(Popover.DATA_KEY).updateContent(content);
        },

        getHintContent: function() {
            return this.getElement('pricesHintContent').html() || '';
        },

        setPrices: function() {
            this.prices = PricesHelper.preparePrices(this.model.get('prices'));
            this.foundPrice = {};

            if (!this.rendered) {
                this.setFoundPrice(true);
                if (!this.rendered) {
                    this.updateUI();
                }
            } else {
                this.setFoundPrice();
            }
        },

        onQuantityChange: function(options) {
            this.setFoundPrice();
        },

        onUnitChange: function(options) {
            this.setFoundPrice(options.manually || false);
        },

        setUnitLabel: function(clear) {
            if (!this.changeUnitLabel) {
                return;
            }

            const price = this.model.get('price');
            let unitLabel = null;
            if (!clear && price) {
                unitLabel = _.__('oro.pricing.price.formatted.unit', {
                    formattedUnit: _(price.formatted_unit).capitalize(),
                    formattedPrice: price.formatted_price
                });
            }

            this.model.set('unit_label', unitLabel);
        },

        setFoundPrice: function(changeQuantity) {
            this.setPriceValue(this.findPrice(changeQuantity));
        },

        setPriceValue: function(price) {
            this.model.set('price', price);
            this.setUnitLabel();
        },

        findPrice: function(changeQuantity) {
            if (this.model.get('quantity_changed_manually')) {
                this.changeQuantity = false;
            }

            const quantity = this.model.get('quantity');
            const unit = this.model.get('unit');
            changeQuantity = changeQuantity && this.changeQuantity;

            const foundKey = unit + ' ' + quantity + ' ' + (changeQuantity ? 1 : 0);
            let price = this.foundPrice[foundKey] || null;

            if (!price) {
                if (changeQuantity) {
                    price = _.last(this.prices[unit]) || null;// sorted by quantity, get smallest
                } else {
                    price = PricesHelper.findPrice(this.prices, unit, quantity);
                }

                this.foundPrice[foundKey] = price;
            }

            if (changeQuantity) {
                const setQuantity = price ? price.quantity : this.options.defaultQuantity;
                if (quantity.toString() !== setQuantity.toString()) {
                    this.model.set('quantity', setQuantity);
                }
            }

            return price;
        },

        findPriceValue: function() {
            const price = this.findPrice();
            return price ? price.price : null;
        },

        updateUI: function() {
            this.rendered = true;
            const price = this.model.get('price');
            if (price === null) {
                this.getElement('price').addClass('hidden');
                this.getElement('priceNotFound').removeClass('hidden');
            } else {
                this.getElement('unit').html(this.unitTemplate({price: price}));

                this.getElement('priceValue').html(this.priceTemplate({
                    price: price,
                    numberFormatter: numberFormatter,
                    localeSettings: localeSettings,
                    numeral: numeral
                }));

                this.getElement('priceNotFound').addClass('hidden');
                this.getElement('price').removeClass('hidden');
            }
            this.renderHint();
            mediator.trigger('layout:reposition');
        }
    }));

    return BaseProductPricesView;
});
