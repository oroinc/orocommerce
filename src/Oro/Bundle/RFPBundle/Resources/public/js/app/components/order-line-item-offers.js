define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');

    const OrderLineItemOffers = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            offersSelector: '.order-line-item-offers',
            priceSelector: '.order-line-item-price input',
            quantitySelector: '.order-line-item-quantity input',
            unitSelector: '.order-line-item-quantity select',
            productSelector: '.order-line-item-product input',
            productSkuSelector: '.order-line-item-product-sku input',
            offersDataSelector: '.order-line-item-offers-data input'
        },

        /**
         * @property {Object}
         */
        objects: {},

        /**
         * @property {jQuery.Element}
         */
        $product: null,

        /**
         * @property {Object}
         */
        items: [],

        /**
         * @inheritdoc
         */
        constructor: function OrderLineItemOffers(options) {
            OrderLineItemOffers.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('click', this.options.offersSelector, this.onRadioClick.bind(this));

            this.$product = $(this.options.productSelector);
            this.$product
                .on('change', this.onProductChange.bind(this));

            this.objects = {};
            // Init order with RFQ items
            mediator.trigger('entry-point:order:trigger-delayed');
        },

        /**
         * @param {jQuery.Event} e
         */
        onRadioClick: function(e) {
            const $target = $(e.target);
            let hasChanges = false;

            // Disable entry point listeners and load data after all values are set.
            // Listeners will be enabled by the entry point after AJAX request processed.
            mediator.trigger('entry-point:listeners:off');
            // Fill corresponding inputs with values from data attributes of offer if value differs.
            _.each(['quantity', 'price', 'unit'], (function(field) {
                const data = $target.data(field).toString();
                const $el = this.findObject(this.options[field + 'Selector']);
                if (data.length > 0 && $el.val() !== data) {
                    $el.val(data).trigger('change');
                    hasChanges = true;
                }
            }.bind(this)));

            if (hasChanges) {
                mediator.trigger('entry-point:order:trigger');
            }
        },

        onProductChange: function() {
            $(this.options.offersDataSelector).val(null);
            $(this.options.productSkuSelector).val(null);
            this.$product.off();
            this.options._sourceElement.remove();
        },

        /**
         * @param {String} selector
         * @return {jQuery.Object}
         */
        findObject: function(selector) {
            if (this.objects[selector]) {
                return this.objects[selector];
            }

            this.objects[selector] = $(selector);

            return this.objects[selector];
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$product.off();
            this.options._sourceElement.off();

            OrderLineItemOffers.__super__.dispose.call(this);
        }
    });

    return OrderLineItemOffers;
});
