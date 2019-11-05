define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const _ = require('underscore');
    const $ = require('jquery');
    const routing = require('routing');

    const QuoteProductToOrderComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            offerSelector: '.radiobox',
            quantitySelector: '.quantity',
            unitInputSelector: '.unitInput',
            unitSelector: '.unit',
            unitPriceSelector: '.unitPrice',
            data_attributes: {
                unit: 'unit',
                formatted_unit: 'formatted-unit',
                quantity: 'quantity',
                price: 'price',
                allow_increment: 'allow-increment'
            },
            matchOfferRoute: 'oro_sale_quote_frontend_quote_product_match_offer',
            quoteProductId: null,
            quoteDemandId: null,
            calculatingMessage: 'Calculating...',
            notAvailableMessage: 'N/A'
        },

        /**
         * @property {jQuery.Element}
         */
        $quantity: null,

        /**
         * @property {jQuery.Element}
         */
        $unitInput: null,

        /**
         * @property {jQuery.Element}
         */
        $unit: null,

        /**
         * @property {jQuery.Element}
         */
        $unitPrice: null,

        /**
         * @property {jQuery.Element}
         */
        $offerSelector: null,

        /**
         * @property {Number}
         */
        quantityChange: null,

        /**
         * @property {Boolean}
         */
        quantityEventsEnabled: true,

        /**
         * @inheritDoc
         */
        constructor: function QuoteProductToOrderComponent(options) {
            QuoteProductToOrderComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = options._sourceElement;
            this.blockQuantityUpdate = false;
            this.$quantity = this.$el.find(this.options.quantitySelector);
            this.$unitInput = this.$el.find(this.options.unitInputSelector);
            this.$unit = this.$el.find(this.options.unitSelector);
            this.$unitPrice = this.$el.find(this.options.unitPriceSelector);
            this.$offerSelector = this.$el.find(this.options.offerSelector);

            this.$offerSelector.on('change', _.bind(this.onOfferChange, this));
            this.addQuantityEvents();
        },

        /**
         * @param {Event} e
         */
        onOfferChange: function(e) {
            const target = $(e.target);

            this.quantityEventsEnabled = false;

            if (!this.blockQuantityUpdate) {
                this.updateQuantityInputValue(Number(target.data(this.options.data_attributes.quantity)));
            }
            this.setValidAttribute(this.$quantity, true);
            this.updateUnitValue(
                String(target.data(this.options.data_attributes.unit)),
                String(target.data(this.options.data_attributes.formatted_unit))
            );
            this.updateUnitPriceValue(String(target.data(this.options.data_attributes.price)));
            this.updateSubtotals();
            this.quantityEventsEnabled = true;
        },

        onQuantityChange: function() {
            const self = this;
            const quantity = this.$quantity.val();
            if (!this.isQuantityValueValid(quantity)) {
                return;
            }

            $.ajax({
                url: routing.generate(
                    this.options.matchOfferRoute,
                    {
                        id: this.options.quoteProductId,
                        demandId: this.options.quoteDemandId,
                        unit: this.$unitInput.val(),
                        qty: quantity
                    }
                ),
                type: 'GET',
                success: function(response) {
                    if (!_.isEmpty(response)) {
                        self.updateUnitPriceValue(String(response.price));
                        self.updateSelector(response.id);
                        self.setValidAttribute(self.$quantity, true);
                        self.updateSubtotals();
                    } else {
                        self.updateUnitPriceValue(self.options.notAvailableMessage);
                        self.setValidAttribute(self.$quantity, false);
                    }
                }
            });
        },

        updateSubtotals: function(value) {
            this.$el.trigger('quote-items-changed');
        },

        /**
         * @param {String} value
         * @returns {Boolean}
         */
        isQuantityValueValid: function(value) {
            const floatValue = parseFloat(value);
            return !_.isNaN(floatValue) && floatValue > 0;
        },

        /**
         * @param {Object} field
         * @param {Boolean} value
         */
        setValidAttribute: function(field, value) {
            const $field = $(field);
            $field.data('valid', value);
            $field.attr('data-valid', value.toString());
            $field.valid();
        },

        addQuantityEvents: function() {
            const disableFixedQuoteQuantityChange = Boolean(_.reduce(this.$offerSelector, function(disable, element) {
                return disable &= !$(element).data(this.options.data_attributes.allow_increment);
            }, true, this));

            this.$quantity.prop('readonly', disableFixedQuoteQuantityChange);
            this.$quantity.toggleClass('disabled', disableFixedQuoteQuantityChange);

            this.$quantity.on('change', _.bind(function() {
                if (!this.quantityEventsEnabled) {
                    return;
                }
                if (this.quantityChange) {
                    clearTimeout(this.quantityChange);
                }
                this.onQuantityChange();
            }, this));

            this.$quantity.on('keyup', _.bind(function() {
                if (this.isQuantityValueValid(this.$quantity.val())) {
                    this.updateUnitPriceValue(this.options.calculatingMessage);
                } else {
                    this.updateUnitPriceValue(this.options.notAvailableMessage);
                }
                if (!this.quantityEventsEnabled) {
                    return;
                }
                if (this.quantityChange) {
                    clearTimeout(this.quantityChange);
                }
                this.setValidAttribute(this.$quantity, true);
                this.quantityChange = setTimeout(_.bind(this.onQuantityChange, this), 1500);
            }, this));
        },

        /**
         * @param {Number} quantity
         */
        updateQuantityInputValue: function(quantity) {
            this.$quantity.val(quantity);
            this.$quantity.data(this.options.data_attributes.quantity, quantity);
        },

        /**
         * @param {String} unit
         * @param {String} formattedUnit
         */
        updateUnitValue: function(unit, formattedUnit) {
            this.$unitInput.val(unit);
            this.$unit.text(formattedUnit);
        },

        /**
         * @param {String} price
         */
        updateUnitPriceValue: function(price) {
            this.$unitPrice.text(price);
        },

        /**
         * @param {Integer} id
         */
        updateSelector: function(id) {
            this.blockQuantityUpdate = true;
            const selector = $(this.options.offerSelector + '[data-value="' + id + '"]');
            selector.prop('checked', 'checked');
            selector.trigger('change');
            this.blockQuantityUpdate = false;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            QuoteProductToOrderComponent.__super__.dispose.call(this);
        }
    });

    return QuoteProductToOrderComponent;
});
