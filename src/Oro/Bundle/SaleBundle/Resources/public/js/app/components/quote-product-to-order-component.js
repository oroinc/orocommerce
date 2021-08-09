define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const _ = require('underscore');
    const $ = require('jquery');
    const routing = require('routing');
    /** @var QuantityHelper QuantityHelper **/
    const QuantityHelper = require('oroproduct/js/app/quantity-helper');

    const QuoteProductToOrderComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            unitPrecisions: null,
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
         * @inheritdoc
         */
        constructor: function QuoteProductToOrderComponent(options) {
            QuoteProductToOrderComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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

            this.$offerSelector.on('change', this.onOfferChange.bind(this));
            this.addQuantityEvents();
            this.updateQuantityInputPrecision(this.$offerSelector.filter(':checked'));
        },

        /**
         * @param {Event} e
         */
        onOfferChange: function(e) {
            const target = $(e.target);

            this.quantityEventsEnabled = false;

            this.updateQuantityInputPrecision(target);
            if (!this.blockQuantityUpdate) {
                this.updateQuantityInputValue(target.data(this.options.data_attributes.quantity));
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
            if (!QuantityHelper.isQuantityLocalizedValueValid(quantity)) {
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

            this.$quantity.on('change', () => {
                if (!this.quantityEventsEnabled) {
                    return;
                }
                if (this.quantityChange) {
                    clearTimeout(this.quantityChange);
                }
                this.onQuantityChange();
            });

            this.$quantity.on('keyup', () => {
                if (QuantityHelper.isQuantityLocalizedValueValid(this.$quantity.val())) {
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
                this.quantityChange = setTimeout(this.onQuantityChange.bind(this), 1500);
            });
        },

        /**
         * @param {Number} quantity
         */
        updateQuantityInputValue: function(quantity) {
            this.$quantity.val(QuantityHelper.formatQuantity(quantity));
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

        updateQuantityInputPrecision: function(target) {
            const unit = target.data(this.options.data_attributes.unit);
            if (unit in this.options.unitPrecisions) {
                this.$quantity.data('precision', this.options.unitPrecisions[unit]).inputWidget('refresh');
            }
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
