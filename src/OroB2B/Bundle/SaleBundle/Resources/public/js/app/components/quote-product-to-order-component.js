/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var QuoteProductToOrderComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');

    QuoteProductToOrderComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            offerSelector: '.radiobox',
            quantitySelector: '.quantity',
            unitSelector: '.unit',
            unitPriceSelector: '.unitPrice',
            data_attributes: {
                unit: 'unit',
                quantity: 'quantity',
                allow_increment: 'allow-increment',
                price: 'price'
            }
        },

        /**
         * @property {jQuery.Element}
         */
        $quantity: null,

        /**
         * @property {jQuery.Element}
         */
        $unit: null,

        /**
         * @property {jQuery.Element}
         */
        $unitPrice: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$quantity = $(this.options.quantitySelector);
            this.$unit = $(this.options.unitSelector);
            this.$unitPrice = $(this.options.unitPriceSelector);

            this.options._sourceElement.on('change', this.options.offerSelector, _.bind(this.onSelectorChange, this));
        },

        /**
         * @param {Event} e
         */
        onSelectorChange: function(e) {
            var target = $(e.target);

            this.updateQuantityInputValue(Number(target.data(this.options.data_attributes.quantity)));
            this.updateQuantityInputState(Boolean(target.data(this.options.data_attributes.allow_increment)));
            this.updateUnitValue(String(target.data(this.options.data_attributes.unit)));
            this.updateUnitPriceValue(String(target.data(this.options.data_attributes.price)));
        },

        /**
         * @param {Number} quantity
         */
        updateQuantityInputValue: function(quantity) {
            this.$quantity.val(quantity);
            this.$quantity.data(this.options.data_attributes.quantity, quantity);
        },

        /**
         * @param {Boolean} allowIncrement
         */
        updateQuantityInputState: function(allowIncrement) {
            if (allowIncrement) {
                this.$quantity.removeProp('readonly');
            } else {
                this.$quantity.prop('readonly', 'readonly');
            }
        },

        /**
         * @param {String} unit
         */
        updateUnitValue: function(unit) {
            this.$unit.text(unit);
        },

        /**
         * @param {String} price
         */
        updateUnitPriceValue: function(price) {
            this.$unitPrice.text(price);
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
