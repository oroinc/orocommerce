/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var QuoteProductToOrderComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');

    QuoteProductToOrderComponent = BaseComponent.extend({
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
                quantity: 'quantity',
                price: 'price'
            },
            matchOfferRoute: 'orob2b_sale_quote_frontend_quote_product_match_offer',
            quoteProductId: null
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
         * @property {Number}
         */
        quantityChange: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$quantity = $(this.options.quantitySelector);
            this.$unitInput = $(this.options.unitInputSelector);
            this.$unit = $(this.options.unitSelector);
            this.$unitPrice = $(this.options.unitPriceSelector);

            this.options._sourceElement.on('change', this.options.offerSelector, _.bind(this.onSelectorChange, this));
            this.addQuantityEvents();
        },

        /**
         * @param {Event} e
         */
        onSelectorChange: function(e) {
            var target = $(e.target);

            this.updateQuantityInputValue(Number(target.data(this.options.data_attributes.quantity)));
            this.updateUnitValue(String(target.data(this.options.data_attributes.unit)));
            this.updateUnitPriceValue(String(target.data(this.options.data_attributes.price)));
        },

        onQuantityChange: function() {
            var self = this;
            var quantity = parseFloat(this.$quantity.val());

            if (_.isNaN(quantity) || quantity <= 0) {
                return;
            }

            $.ajax({
                url: routing.generate(
                    this.options.matchOfferRoute,
                    {
                        id: this.options.quoteProductId,
                        unit: this.$unitInput.val(),
                        qty: quantity
                    }
                ),
                type: 'GET',
                success: function(response) {
                    if (!_.isEmpty(response)) {
                        self.updateUnitPriceValue(String(response.price));
                        self.updateSelector(response.id);
                        self.$quantity.data('valid', true);
                    } else {
                        self.$quantity.data('valid', false);
                    }
                    self.$quantity.valid();
                }
            });
        },

        addQuantityEvents: function() {
            this.$quantity.change(_.bind(function() {
                if (this.quantityChange) {
                    clearTimeout(this.quantityChange);
                }
                this.onQuantityChange.call(this);
            }, this));

            this.$quantity.keyup(_.bind(function() {
                if (this.quantityChange) {
                    clearTimeout(this.quantityChange);
                }
                this.quantityChange = setTimeout(_.bind(this.onQuantityChange, this), 1500);
            }, this));
        },

        /**
         * @param {Number} quantity
         */
        updateQuantityInputValue: function(quantity) {
            this.$quantity.val(quantity);
            this.$quantity.data(this.options.data_attributes.quantity, quantity);
            this.$quantity.trigger('blur');
        },

        /**
         * @param {String} unit
         */
        updateUnitValue: function(unit) {
            this.$unitInput.val(unit);
            this.$unit.text(unit);
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
            $(this.options.offerSelector + '[value="' + id + '"]').prop('checked', 'checked');
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
