define(function(require) {
    'use strict';

    var LineItemOfferView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var ProductPricesComponent = require('orob2bpricing/js/app/components/product-prices-component');

    LineItemOfferView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            $: {
                product: '',
                priceValue: '',
                priceType: '',
                productUnit: '',
                quantity: '',
                currency: ''
            },
            isNew: false
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            _.each(this.options.$, _.bind(function(selector, field) {
                this.options.$[field] = $(selector);
            }, this));
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.initPrices();
        },

        initPrices: function() {
            this.subview('productPricesComponents', new ProductPricesComponent({
                _sourceElement: this.$el,
                $product: this.options.$.product,
                $priceValue: this.options.$.priceValue,
                $priceType: this.options.$.priceType,
                $productUnit: this.options.$.productUnit,
                $quantity: this.options.$.quantity,
                $currency: this.options.$.currency,
                isNew: this.options.isNew
            }));
        }
    });

    return LineItemOfferView;
});
