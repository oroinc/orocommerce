define(function(require) {
    'use strict';

    var ProductAutocompleteComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var AutocompleteComponent = require('oro/autocomplete-component');

    ProductAutocompleteComponent = AutocompleteComponent.extend({
        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            var thisOptions = {
                product: {},
                productBySkuRoute: 'orob2b_product_frontend_ajax_names_by_skus',
                selectors: {
                    row: '.quick-order__form__row',
                    name: '.product-autocomplete-name',
                    error: '.product-autocomplete-error',
                    success: '.product-autocomplete-success'
                },
                errorClass: 'error'
            };
            this.options = $.extend(true, thisOptions, this.options);

            ProductAutocompleteComponent.__super__.initialize.apply(this, arguments);

            this.$row = this.$el.closest(this.options.selectors.row);
            this.$name = this.$row.find(this.options.selectors.name);
            this.$error = this.$row.find(this.options.selectors.error);
            this.$success = this.$row.find(this.options.selectors.success);

            this.product = $.extend(true, {
                sku: null,
                name: null
            }, this.options.product);
            this.updateProduct();

            this.$el.change(_.bind(this.change, this));
        },

        change: function() {
            var self = this;
            this.resetProduct();

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

            if (this.product.sku || !val) {
                this.updateProduct();
            } else {
                this.validateProduct(val);
            }
        },

        validateProduct: function(val) {
            var self = this;
            $.ajax({
                url: routing.generate(this.options.productBySkuRoute),
                data: {skus: [val]},
                type: 'post',
                success: function(response) {
                    val = self.$el.val().toUpperCase();
                    if (response[val]) {
                        self.product.sku = val;
                        self.product.name = response[val].name;
                    }
                    self.updateProduct();
                },
                error: function() {
                    self.updateProduct();
                }
            });
        },

        resetProduct: function() {
            this.product.sku = this.product.name = null;

            this.$name.hide().find('span').html('');
            this.$success.hide();
            this.$error.hide();
            this.$el.removeClass(this.options.errorClass);
        },

        updateProduct: function() {
            if (this.product.sku) {
                this.$el.val(this.product.sku);
                this.$name.show().find('span').html(this.product.name);
                this.$success.show();
            } else if (this.$el.val().length > 0) {
                this.$error.show();
                this.$el.addClass(this.options.errorClass);
            }
        }
    });

    return ProductAutocompleteComponent;
});
