define(function(require) {
    'use strict';

    var ProductAutocompleteComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ProductHelper = require('oroproduct/js/app/product-helper');
    var AutocompleteComponent = require('oro/autocomplete-component');

    ProductAutocompleteComponent = AutocompleteComponent.extend({
        /**
         * @property {Object}
         */
        product: {},

        /**
         * @property {String}
         */
        previousValue: '',

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            var thisOptions = {
                product: {},
                productBySkuRoute: 'oro_frontend_autocomplete_search',
                selectors: {
                    row: '[data-role="row"]',
                    sku: '[data-name="field__product-sku"]',
                    error: '[data-role="autocomplete-error"]'
                },
                errorClass: 'error'
            };
            this.options = $.extend(true, thisOptions, options);

            ProductAutocompleteComponent.__super__.initialize.apply(this, arguments);

            this.$row = this.$el.closest(this.options.selectors.row);
            this.$error = this.$row.find(this.options.selectors.error);

            this.product = $.extend(true, {
                sku: null,
                name: null,
                displayName: null
            }, this.options.product);

            this.updateProduct();

            this.$el.on('blur' + this.eventNamespace(), _.debounce(_.bind(this.onBlur, this), 150));
        },

        eventNamespace: function() {
            return '.product-autocomplete-component';
        },

        onBlur: function(e, attributes) {
            var val = ProductHelper.trimWhiteSpace(e.target.value);
            var hasChanged = val !== this.previousValue;
            var $autoComplete = $(e.relatedTarget).parents('ul.select2-results');

            if (hasChanged && !$autoComplete.length) {
                this.previousValue = val;
                this.resetProduct();
                this.searchProduct(attributes ? attributes.sku : val);
            }
        },

        updater: function(item) {
            this.resetProduct();
            this.previousValue = item;
            this.validateProductByName(item);

            return item;
        },

        searchProduct: function(query) {
            var queryParts = query.split(' ');
            var sku = queryParts.length > 1 ? queryParts[0] : query;

            this.disposed = true;
            var self = this;
            $.ajax({
                url: self.url,
                method: 'get',
                data: {query: sku},
                type: 'post',
                success: function(response) {
                    self.disposed = false;
                    self.prepareResults(response);
                    self.validateProductBySku(sku);
                }
            });
        },

        validateProductBySku: function(sku) {
            var self = this;
            var isExist = false;
            _.map(self.resultsMapping || [], function(product) {
                if (product.sku.toUpperCase() === sku.toUpperCase()) {
                    isExist = true;
                    self.validateProduct(product);
                }
            });

            if (!isExist) {
                mediator.trigger('autocomplete:productNotFound', {
                    item: {sku: sku},
                    $el: this.$el
                });
            }
            this.updateProduct();
        },

        validateProductByName: function(name) {
            var product = this.resultsMapping ? this.resultsMapping[name] : null;
            if (product) {
                this.validateProduct(product);
            } else {
                var nameParts = name.split(' ');
                mediator.trigger('autocomplete:productNotFound', {
                    item: {sku: nameParts.length > 1 ? nameParts[0] : name},
                    $el: this.$el
                });
            }
            this.updateProduct();
        },

        validateProduct: function(product) {
            this.product.sku = product.sku;
            this.product.name = product['defaultName.string'];
            this.product.displayName = [this.product.sku, this.product.name].join(' - ');

            mediator.trigger('autocomplete:productFound', {
                item: product,
                $el: this.$el
            });
            this.previousValue = this.product.displayName;
        },

        resetProduct: function() {
            this.product.sku = this.product.name = this.product.displayName = null;

            this.$error.hide();
            this.$el.removeClass(this.options.errorClass);
        },

        updateProduct: function() {
            if (this.product.sku) {
                this.$el.val(this.product.displayName);
            } else if (this.$el && this.$el.val().length > 0) {
                this.$error.show();

                // move setting class to next processor tick so it's correctly set after submitting the form
                _.defer(_.bind(function() {
                    this.$el.addClass(this.options.errorClass);
                }, this));
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.product;
            delete this.previousValue;

            this.$el.off(this.eventNamespace());

            ProductAutocompleteComponent.__super__.dispose.call(this);
        }
    });

    return ProductAutocompleteComponent;
});
