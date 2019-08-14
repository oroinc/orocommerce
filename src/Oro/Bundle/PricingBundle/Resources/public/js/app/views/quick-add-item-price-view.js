define(function(require) {
    'use strict';

    var QuickAddItemPriceView;
    var BaseProductPricesView = require('oropricing/js/app/views/base-product-prices-view');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    QuickAddItemPriceView = BaseProductPricesView.extend({
        /**
         * @property {Object}
         */
        options: _.extend({}, BaseProductPricesView.prototype.options, {
            defaultQuantity: '',
            subtotalNotAvailable: __('oro.pricing.price.not_available')
        }),

        elements: _.extend({}, BaseProductPricesView.prototype.elements, {
            subtotal: '[data-name="field__product-subtotal"]',
            pricesHintContentRendered: '[data-class="prices-hint-content"]'
        }),

        modelElements: {
            subtotal: 'subtotal'
        },

        modelAttr: _.extend({}, BaseProductPricesView.prototype.modelAttr, {
            subtotal: null
        }),

        modelEvents: _.extend({}, BaseProductPricesView.prototype.modelEvents, {
            quantity: ['change', 'updateSubtotal'],
            unit: ['change', 'updateSubtotal'],
            prices: ['change', 'updateSubtotal'],
            subtotal: ['change', 'updateUI']
        }),

        listen: {
            'autocomplete:productFound mediator': 'updateModel',
            'quick-add-item:model-change mediator': 'updateModel'
        },

        templates: {},

        /**
         * @inheritDoc
         */
        constructor: function QuickAddItemPriceView() {
            QuickAddItemPriceView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            QuickAddItemPriceView.__super__.initialize.apply(this, arguments);
        },

        updateModel: function(data) {
            if (!this.checkEl(data.$el)) {
                return;
            }

            var prices = {};
            _.each(data.item.prices, function(unitPrices, unit) {
                _.each(unitPrices, function(priceObject) {
                    var index = [unit, priceObject.quantity].join('_');
                    prices[index] = priceObject;
                }, this);
            }, this);

            this.changeQuantity = !this.model.get('quantity_changed_manually');

            if (!data.item.sku) {
                this.model.set({
                    subtotal: '',
                    prices: {}
                });
            } else if (data.item.quantity) {
                this.model.set({
                    quantity: data.item.quantity,
                    quantity_changed_manually: data.item.quantity_changed_manually,
                    unit: data.item.unit,
                    showSubtotalPlaceholder: data.item.unit !== data.item.unit_placeholder
                });
            } else {
                this.model.set({
                    quantity: this.model.get('quantity') || this.options.defaultQuantity,
                    quantity_changed_manually: this.model.get('quantity'),
                    units: data.item.units,
                    product_units: data.item.units,
                    prices: _.isEmpty(prices) ? this.model.get('prices') : prices
                });
            }
        },

        onUnitChange: function() {
            this.setFoundPrice(true);
        },

        checkEl: function($el) {
            return $el !== undefined &&
                this.$el.closest('.quick-order-add__row').attr('id') ===
                $el.closest('.quick-order-add__row').attr('id');
        },

        updateSubtotal: function() {
            this.model.set('subtotal', this.getSubtotal());
        },

        getSubtotal: function() {
            var priceObj = this.model.get('price');
            var quantity = this.model.get('quantity');

            if (priceObj && quantity) {
                return NumberFormatter.formatCurrency(
                    priceObj.price * quantity,
                    priceObj.currency
                );
            } else if (this.model.get('showSubtotalPlaceholder')) {
                return this.options.subtotalNotAvailable;
            }

            return null;
        },

        initHint: function() {
            this.hintInitialized = true;
            this.templates.pricesHintContent = _.template(this.getElement('pricesHintContent').text());

            var $pricesHint = $(_.template(this.getElement('pricesHint').text())());
            this.$elements.pricesHint = $pricesHint;
            this.getElement('subtotal').after($pricesHint);
        },

        getHintContent: function() {
            if (_.isEmpty(this.prices)) {
                return '';
            }

            return this.templates.pricesHintContent({
                model: this.model.attributes,
                prices: this.prices,
                matchedPrice: this.findPrice(),
                clickable: false,
                formatter: NumberFormatter
            });
        },

        renderHint: function() {
            if (!this.hintInitialized) {
                this.initHint();
            }
            return QuickAddItemPriceView.__super__.renderHint.apply(this, arguments);
        },

        updateUI: function() {
            this.renderHint();

            var $pricesHintEl = this.getElement('pricesHintContentRendered');
            if (this.model.get('subtotal')) {
                $pricesHintEl.show();
            } else {
                $pricesHintEl.hide();
            }
        }
    });

    return QuickAddItemPriceView;
});
