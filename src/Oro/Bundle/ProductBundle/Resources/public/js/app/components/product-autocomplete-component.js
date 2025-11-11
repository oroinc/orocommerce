import $ from 'jquery';
import _ from 'underscore';
import routing from 'routing';
import mediator from 'oroui/js/mediator';
import __ from 'orotranslation/js/translator';
import ProductHelper from 'oroproduct/js/app/product-helper';
import autocompleteErrorTemplate from 'tpl-loader!oroproduct/templates/product-autocomplete-error.html';
import AutocompleteComponent from 'oro/autocomplete-component';

const ProductAutocompleteComponent = AutocompleteComponent.extend({
    /**
     * @property {Object}
     */
    product: {},

    /**
     * @property {String}
     */
    previousValue: '',

    /**
     * @property
     */
    autocompleteErrorTemplate: autocompleteErrorTemplate,

    /**
     * @inheritdoc
     */
    constructor: function ProductAutocompleteComponent(options) {
        ProductAutocompleteComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        const thisOptions = {
            product: {},
            productBySkuRoute: 'oro_frontend_autocomplete_search',
            selectors: {
                row: '[data-role="row"]',
                sku: '[data-name="field__product-sku"]',
                displayName: '[data-name="field__product-display-name"]',
                error: '[data-role="autocomplete-error"]'
            },
            errorClass: 'error'
        };
        this.options = $.extend(true, thisOptions, options);

        ProductAutocompleteComponent.__super__.initialize.call(this, options);

        this.$row = this.$el.closest(this.options.selectors.row);
        this.$sku = this.$row.find(this.options.selectors.sku);
        this.$displayName = this.$row.find(this.options.selectors.displayName);

        this.product = $.extend(true, {
            sku: this.$sku.val() || null,
            name: null,
            displayName: this.$el.val() || null
        }, this.options.product);

        this.updateProduct();

        this.$el.on(`blur${this.eventNamespace()}`, _.debounce(this.onBlur.bind(this), 150));
        this.$el.on(`input${this.eventNamespace()}`, this.abortRequestIfEmpty.bind(this));

        this.$sku.on(`validate-element${this.eventNamespace()}`, ({invalid, errorClass}) => {
            if (invalid && this.$el && this.$el.val().length > 0) {
                // updates SKU validation message in case product name is not empty
                this.$row
                    .find('.fields-row-error [id*=productSku].validation-failed span:last-child')
                    .text(_.escape(_.__('oro.product.validation.sku.not_found')));
            }
            _.defer(() => {
                this.$displayName.toggleClass(errorClass, invalid);
            });
        });

        mediator.on('autocomplete:validate-response', this.validateResponse, this);
    },

    eventNamespace: function() {
        return '.product-autocomplete-component';
    },

    onBlur: function(e) {
        if (this.disposed) {
            return;
        }

        const val = ProductHelper.trimWhiteSpace(e.target.value);
        const hasChanged = this.hasChanged(val);
        const $autoComplete = $(e.relatedTarget).parents('ul.select2-results');

        if (hasChanged && !$autoComplete.length) {
            this.resetProduct();
            this.validateProduct(val);
        }
    },

    abortRequestIfEmpty() {
        if (!this.disposed && !this.$el.val() && this.jqXHR) {
            this.jqXHR.abort();
        }
    },

    _searchForResults(...args) {
        ProductAutocompleteComponent.__super__._searchForResults.apply(this, args);
        this.abortRequestIfEmpty();
    },

    hasChanged: function(value) {
        return value !== this.previousValue;
    },

    updater: function(item) {
        const resultMapping = this.resultsMapping[item];

        this.resetProduct();
        this.validateProduct(this.getProductDisplayName(resultMapping));

        return this.getProductDisplayName(resultMapping);
    },

    validateProduct: function(val) {
        const [sku, name] = this.parseVal(val);

        this.sendValidateProductRequest({
            name: 'oro_product_visibility_limited_with_prices',
            per_page: 1,
            sku: [sku],
            productName: name,
            query: val
        });
    },

    sendValidateProductRequest: function(data) {
        $.ajax({
            url: routing.generate(this.options.productBySkuRoute),
            method: 'post',
            data: data,
            success: response => {
                if (this.disposed) {
                    return;
                }
                this.validateResponse(response);
            },
            error: () => {
                if (this.disposed) {
                    return;
                }
                this.$el.trigger({type: 'productNotFound.autocomplete', item: {sku: data.sku[0]}});
                this.resetProduct();
            }
        });
    },

    validateResponse: function(response) {
        const val = this.$el.val();

        // proceed check only for non-empty value that was changed
        if (!!val && this.hasChanged(val)) {
            this.previousValue = val;
            this.resetProduct();

            const item = this.findItem(response.results, val);
            if (item) {
                this.product.sku = item.sku;
                this.product.name = item['defaultName.string'];
                this.product.displayName = this.getProductDisplayName(item);
                this.$el.trigger({type: 'productFound.autocomplete', item});
                this.previousValue = this.product.displayName;
            } else {
                this.$el.trigger({type: 'productNotFound.autocomplete', item: {sku: val}});
            }
            this.updateProduct();
        } else if (this.hasChanged(val)) {
            // Clear state when value changed and empty
            this.previousValue = val;
            this.resetProduct();
        }
    },

    findItem: function(items, val) {
        const [sku, name] = this.parseVal(val);
        let item = null;

        if (name) {
            item = _.find(items, function(resultItem) {
                return resultItem.sku.toUpperCase() === sku.toUpperCase() &&
                    resultItem['defaultName.string'] === name;
            });
        }
        if (!item) {
            item = _.find(items, function(resultItem) {
                return resultItem.sku.toUpperCase() === sku.toUpperCase();
            });
        }

        return item;
    },

    getProductDisplayName: function(item) {
        return [item.sku, item['defaultName.string']].join(' - ');
    },

    parseVal: function(val) {
        const namePos = val.indexOf(' ');
        if (namePos === -1) {
            return [val, null];
        }

        const sku = val.substring(val, namePos);
        let name = val.substring(namePos + 1).trim();
        if (name && name.indexOf('-') === 0) {
            name = name.substring(1).trim();
        }

        return [sku, name];
    },

    resetProduct: function() {
        this.product.sku = this.product.name = this.product.displayName = null;

        this.hideError();
        this.$el.removeClass(this.options.errorClass);
    },

    updateProduct: function() {
        if (this.product.sku) {
            this.$el.val(this.product.displayName);
            this.$sku.valid();
        } else if (this.$el && this.$el.val().length > 0) {
            this.showError();

            // move setting class to next processor tick so it's correctly set after submitting the form
            _.defer(() => {
                this.$el.addClass(this.options.errorClass);
            });
        }
    },

    showError: function() {
        if (this.$row.data('model')) {
            return this.$row.data('model').set('errors', [{
                propertyPath: 'product',
                source: 'autocomplete',
                message: __('oro.product.validation.sku.not_found')
            }]);
        }

        // remove validation message for hidden SKU input, if it is shown,
        // since it duplicates autocomplete validation message
        this.$row.find('.fields-row-error [id*=productSku].validation-failed').remove();
        if (this.$error) {
            this.$error.show();
        } else {
            this.$row.find('.fields-row-error').append(this.autocompleteErrorTemplate());
            this.$error = this.$row.find(this.options.selectors.error);
        }
    },

    hideError: function() {
        if (this.$row.data('model')) {
            const errors = this.$row.data('model').get('errors');
            return this.$row.data('model').set('errors', errors.filter(err => err.source !== 'autocomplete'));
        }

        if (this.$error) {
            this.$error.hide();
        }
    },

    show: function() {
        ProductAutocompleteComponent.__super__.show.call(this);
        this.focus();
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        delete this.product;
        delete this.previousValue;

        this.$sku.off(this.eventNamespace());
        this.$el.off(this.eventNamespace());
        mediator.off('autocomplete:validate-response', this.validateResponse, this);

        ProductAutocompleteComponent.__super__.dispose.call(this);
    }
});

export default ProductAutocompleteComponent;
