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
            ftid: ''
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
            if (!this.options.ftid) {
                this.options.ftid = this.$el.closest('div[data-content]').data('content').toString()
                    .replace(/[^a-zA-Z0-9]+/g, '_').replace(/_+$/, '');
            }

            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$fields = this.$el.find(':input[data-ftid]');

            this.fieldsByName = {};
            this.$fields.each(_.bind(function(i, field) {
                field = $(field);
                var name = this.normalizeName(field.data('ftid').replace(this.options.ftid + '_', ''));
                this.fieldsByName[name] = field;
            }, this));

            var $productEl = this.$el.closest('.sale-quoteproduct-collection-table');
            this.fieldsByName.product = $productEl.find(':input[data-ftid="' + $productEl.data('ftid') + '_product"]');

            this.initPrices();
        },

        /**
         * Convert name with "_" to name with upper case, example: some_name > someName
         *
         * @param {String} name
         *
         * @returns {String}
         */
        normalizeName: function(name) {
            name = name.split('_');
            for (var i = 1, iMax = name.length; i < iMax; i++) {
                name[i] = name[i][0].toUpperCase() + name[i].substr(1);
            }
            return name.join('');
        },

        initPrices: function() {
            this.subview('productPricesComponents', new ProductPricesComponent({
                _sourceElement: this.$el,
                $product: this.fieldsByName.product,
                $priceValue: this.fieldsByName.priceValue,
                $productUnit: this.fieldsByName.productUnit
            }));
        }
    });

    return LineItemView;
});
