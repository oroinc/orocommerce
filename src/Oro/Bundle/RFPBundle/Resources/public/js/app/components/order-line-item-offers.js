define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const _ = require('underscore');
    const $ = require('jquery');

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
                .on('click', this.options.offersSelector, _.bind(this.onRadioClick, this));

            this.$product = $(this.options.productSelector);
            this.$product
                .on('change', _.bind(this.onProductChange, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onRadioClick: function(e) {
            const target = $(e.target);

            const quantity = target.data('quantity');
            if (this.findObject(this.options.quantitySelector) !== quantity) {
                this.findObject(this.options.quantitySelector).val(quantity);
            }

            const unit = target.data('unit');
            if (this.findObject(this.options.unitSelector) !== unit) {
                this.findObject(this.options.unitSelector)
                    .val(target.data('unit'))
                    .trigger('change');
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
