define(function(require) {
    'use strict';

    var ProductAutocompleteComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var AutocompleteComponent = require('oro/autocomplete-component');

    ProductAutocompleteComponent = AutocompleteComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var thisOptions = {
                productBySkuRoute: 'orob2b_product_frontend_ajax_names_by_skus',
                selectors: {
                    row: '.fields-row',
                    name: '.product-autocomplete-name',
                    success: '.product-autocomplete-success'
                }
            };
            this.options = $.extend(true, thisOptions, this.options);

            ProductAutocompleteComponent.__super__.initialize.apply(this, arguments);

            this.$row = this.$el.closest(this.options.selectors.row);
            this.$name = this.$row.find(this.options.selectors.name);
            this.$success = this.$row.find(this.options.selectors.success);
            this.product = {
                sku: null,
                name: null
            };

            this.$el.change(_.bind(this.change, this));
        },

        change: function() {
            var self = this;
            this.product.sku = this.product.name = null;

            var val = this.$el.val();
            var autocompleteResult = this.resultsMapping[val] || null;

            if (autocompleteResult) {
                this.product.sku = autocompleteResult.sku;
                this.product.name = autocompleteResult['defaultName.string'];
            } else {
                val = val.toUpperCase();
                _.each(this.resultsMapping, function(autocompleteResult) {
                    if (autocompleteResult.sku === val) {
                        self.product.sku = autocompleteResult.sku;
                        self.product.name = autocompleteResult['defaultName.string'];
                    }
                });
            }

            this.updateProduct();
            if (!this.product.sku) {
                this.validateProduct(val);
            }
        },

        validateProduct: function(val) {
            var self = this;
            $.post(routing.generate(this.options.productBySkuRoute), {
                skus: [val]
            }, function(response) {
                val = self.$el.val().toUpperCase();
                if (response[val]) {
                    self.product.sku = val;
                    self.product.name = response[val].name;
                    self.updateProduct();
                }
            });
        },

        updateProduct: function() {
            if (this.product.sku) {
                this.$el.val(this.product.sku);
                this.$success.show();
            } else {
                this.$success.hide();
            }
            this.$name.html(this.product.name);
        }
    });

    return ProductAutocompleteComponent;
});
