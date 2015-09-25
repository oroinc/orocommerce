define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var ProductPricesComponent = require('orob2bpricing/js/app/components/product-prices-component');

    LineItemView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            isNew: false
        },

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$fields = this.$el.find(':input[name]');

            this.fieldsByName = {};
            this.$fields.each(_.bind(function(i, field) {
                this.fieldsByName[this.formFieldName(field)] = $(field);
            }, this));

            var $productEl = this.$el.closest('.sale-quoteproduct-collection-table');
            this.fieldsByName.product = $productEl.find(':input[name$="[product]"]');
            this.fieldsByName.productReplacement = $productEl.find(':input[name$="[productReplacement]"]');

            this.initPrices();
        },

        /**
         * @param {Object} field
         * @returns {String}
         */
        formFieldName: function(field) {
            var name = '';
            var nameParts = field.name.replace(/.*\[[0-9]+\]/, '').replace(/[\[\]]/g, '_').split('_');
            var namePart;

            for (var i = 0, iMax = nameParts.length; i < iMax; i++) {
                namePart = nameParts[i];
                if (!namePart.length) {
                    continue;
                }
                if (name.length === 0) {
                    name += namePart;
                } else {
                    name += namePart[0].toUpperCase() + namePart.substr(1);
                }
            }
            return name;
        },

        initPrices: function() {
            var $product = $(this.fieldsByName.product).add(this.fieldsByName.productReplacement);
            this.subview('productPricesComponents', new ProductPricesComponent({
                _sourceElement: this.$el,
                $product: $product,
                $priceValue: this.fieldsByName.priceValue,
                $priceType: this.fieldsByName.priceType,
                $productUnit: this.fieldsByName.productUnit,
                $quantity: this.fieldsByName.quantity,
                $currency: this.fieldsByName.priceCurrency,
                isNew: this.options.isNew
            }));
        }
    });

    return LineItemView;
});
