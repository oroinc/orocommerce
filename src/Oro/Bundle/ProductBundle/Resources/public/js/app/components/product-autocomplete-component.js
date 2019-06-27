define(function(require) {
    'use strict';

    var ProductAutocompleteComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
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
         * @inheritDoc
         */
        constructor: function ProductAutocompleteComponent() {
            ProductAutocompleteComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
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
            this.$sku = this.$row.find(this.options.selectors.sku);
            this.$error = this.$row.find(this.options.selectors.error);

            this.product = $.extend(true, {
                sku: null,
                name: null,
                displayName: null
            }, this.options.product);

            this.updateProduct();

            this.$el.on('blur' + this.eventNamespace(), _.debounce(_.bind(this.onBlur, this), 150));

            mediator.on('autocomplete:validate-response', this.validateResponse, this);
        },

        eventNamespace: function() {
            return '.product-autocomplete-component';
        },

        onBlur: function(e) {
            if (this.disposed) {
                return;
            }

            var val = ProductHelper.trimWhiteSpace(e.target.value);
            var hasChanged = this.hasChanged(val);
            var $autoComplete = $(e.relatedTarget).parents('ul.select2-results');

            if (hasChanged && !$autoComplete.length) {
                this.resetProduct();
                this.validateProduct(val);
            }
        },

        hasChanged: function(value) {
            return value !== this.previousValue;
        },

        updater: function(item) {
            var resultMapping = this.resultsMapping[item];

            this.resetProduct();
            this.validateProduct(resultMapping.sku);

            return [resultMapping.sku, resultMapping['defaultName.string']].join(' - ');
        },

        validateProduct: function(val) {
            var self = this;
            val = val.split(' ')[0];

            $.ajax({
                url: routing.generate(this.options.productBySkuRoute),
                method: 'post',
                data: {
                    name: 'oro_product_visibility_limited_with_prices',
                    per_page: 1,
                    query: val
                },
                success: function(response) {
                    self.validateResponse(response);
                },
                error: function() {
                    mediator.trigger('autocomplete:productNotFound', {
                        item: {sku: val},
                        $el: self.$el
                    });
                    self.resetProduct();
                }
            });
        },

        validateResponse: function(response, requestId) {
            var val = this.$el.val();

            // proceed check only for non-empty value that was changed
            if (!!val && this.hasChanged(val)) {
                this.previousValue = val;
                this.resetProduct();

                var needleSku = val.split(' ')[0].toUpperCase();
                var item = _.find(response.results, function(resultItem) {
                    return resultItem.sku.toUpperCase() === needleSku;
                });
                if (item) {
                    this.product.sku = item.sku;
                    this.product.name = item['defaultName.string'];
                    this.product.displayName = [this.product.sku, this.product.name].join(' - ');

                    mediator.trigger('autocomplete:productFound', {
                        item: item,
                        $el: this.$el,
                        requestId: requestId
                    });
                    this.previousValue = this.product.displayName;
                } else {
                    mediator.trigger('autocomplete:productNotFound', {
                        item: {sku: val},
                        $el: this.$el,
                        requestId: requestId
                    });
                }
                this.updateProduct();
            }
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
            mediator.off('autocomplete:validate-response', this.validateResponse, this);

            ProductAutocompleteComponent.__super__.dispose.call(this);
        }
    });

    return ProductAutocompleteComponent;
});
