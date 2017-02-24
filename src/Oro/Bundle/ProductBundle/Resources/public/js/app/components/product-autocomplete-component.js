define(function(require) {
    'use strict';

    var ProductAutocompleteComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var AutocompleteComponent = require('oro/autocomplete-component');

    ProductAutocompleteComponent = AutocompleteComponent.extend({
        /**
         * {Object}
         */
        defer: null,

        /**
         * {String}
         */
        itemFromAutocomplete: null,

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            var thisOptions = {
                product: {},
                productBySkuRoute: 'oro_product_frontend_ajax_names_by_skus',
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

            this.$el.on('blur', _.bind(this.onBlur, this));
            this.$el.on('change', _.bind(this.onChange, this));
        },

        onBlur: function(event) {
            var $autoComplete = $(event.relatedTarget).parents('.typeahead:first');

            // if relatedTarget is typeahead item, there is no need to validate it
            // otherwise updater will be executed
            if (!$autoComplete.length) {
                var val = this.$el.val();
                if (!val || this.itemFromAutocomplete) {
                    return false;
                }
                this.validateProduct(val);
            }
        },

        onChange: function(event) {
            if (!event.originalEvent) {
                return false;
            }
            this.resetProduct();
        },

        /**
         * {@inheritDoc}
         */
        updater: function(item) {
            this.itemFromAutocomplete = item;

            this.product.sku = this.resultsMapping[item].sku;
            this.product.name = this.resultsMapping[item]['defaultName.string'];
            this.updateProduct();

            return this.resultsMapping[item].sku;
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
                },
                complete: function() {
                    self.updateProduct();
                }
            });
        },

        resetProduct: function() {
            this.itemFromAutocomplete = this.product.sku = this.product.name = null;

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
        },

        dispose: function() {
            delete this.defer;
            delete this.itemFromAutocomplete;
            ProductAutocompleteComponent.__super__.dispose.apply(this, arguments);
        }
    });

    return ProductAutocompleteComponent;
});
