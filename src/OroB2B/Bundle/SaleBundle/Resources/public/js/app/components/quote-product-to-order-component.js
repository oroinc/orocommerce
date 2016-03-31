/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var QuoteProductToOrderComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');

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
            subtotalSelector: '#quote-choie-subtotal',
            data_attributes: {
                unit: 'unit',
                formatted_unit: 'formatted-unit',
                quantity: 'quantity',
                price: 'price'
            },
            matchOfferRoute: 'orob2b_sale_quote_frontend_quote_product_match_offer',
            quoteProductId: null,
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
            var target = $(e.target);

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

            this.quantityEventsEnabled = true;
        },

        onQuantityChange: function() {
            var self = this;
            var quantity = this.$quantity.val();
            if (!this.isQuantityValueValid(quantity)) {
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
                        self.setValidAttribute(self.$quantity, true);
                        self.loadSubtotals();
                    } else {
                        self.updateUnitPriceValue(self.options.notAvailableMessage);
                        self.setValidAttribute(self.$quantity, false);
                    }
                }
            });
        },

        loadSubtotals: function(value) {
            //TODO: submit form
            var url = window.location.href;
            var widgetParameters = '_widgetContainer=ajax';
            url += (-1 !== _.indexOf(url, '?') ? '&' : '?') + widgetParameters;

            var data = data || {method: 'GET'};
            data.url = url;
            $.ajax(data)
                .done(_.bind(this.onSubtotalSuccess, this))
                .fail(_.bind(this.onSubtotalFail, this));
        },

        onSubtotalSuccess: function(response) {
            if (response.hasOwnProperty('redirectUrl')) {
                mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
            } else {
                var $response = $('<div/>').html(response);
                var $content = $(this.options.subtotalSelector);
                $content.html($response.find(this.options.subtotalSelector).html());
            }
        },

        onSubtotalFail: function() {
            this.inProgress = false;
            mediator.execute('showFlashMessage', 'error', 'Could not perform transition');
        },

        /**
         * @param {String} value
         * @returns {Boolean}
         */
        isQuantityValueValid: function(value) {
            var floatValue = parseFloat(value);
            return !_.isNaN(floatValue) && floatValue > 0;
        },

        /**
         * @param {Object} field
         * @param {Boolean} value
         */
        setValidAttribute: function(field, value) {
            var $field = $(field);
            $field.data('valid', value);
            $field.valid();
        },

        addQuantityEvents: function() {
            this.$quantity.on('change', _.bind(function() {
                if (!this.quantityEventsEnabled) {
                    return;
                }
                if (this.quantityChange) {
                    clearTimeout(this.quantityChange);
                }
                this.onQuantityChange.call(this);
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
            $(this.options.offerSelector + '[value="' + id + '"]').prop('checked', 'checked');
            $(this.options.offerSelector + '[value="' + id + '"]').trigger('change');
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
