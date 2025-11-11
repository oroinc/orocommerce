import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';

const ProductVariantsEdit = function(options) {
    this.initialize(options);
};

_.extend(ProductVariantsEdit.prototype, {
    constructor: ProductVariantsEdit,

    /**
     * @property {Grid}
     */
    grid: null,

    /**
     * @property {Object}
     */
    options: {
        defaultProductVariantFieldSelector: 'select[name="oro_product[defaultVariant]"]'
    },

    /**
     * @param {Object} [options.grid] grid instance
     * @param {Object} [options.options] grid initialization options
     */
    initialize: function(options) {
        this.grid = options.grid;
        this.defaultProductVariantField = this.grid.$el.closest('form')
            .find(this.options.defaultProductVariantFieldSelector);

        if (this.grid.collection.length === 0) {
            this.clearDefaultVariantSelector();
        }
        this.grid.collection.on('backgrid:selected', this.updateDefaultVariantSelector.bind(this));
    },

    clearDefaultVariantSelector: function() {
        if (this.defaultProductVariantField.length !== 1) {
            return;
        }

        this.defaultProductVariantField.find('option').remove();
    },

    updateDefaultVariantSelector: function(model, selected) {
        if (this.defaultProductVariantField.length !== 1) {
            return;
        }

        this.selectedDefaultVariant = this.defaultProductVariantField.find('option:selected').val();

        this.clearDefaultVariantSelector();

        this.defaultProductVariantField.append($('<option>', {
            value: '',
            text: __('oro.product.product_variants.default_variant.no_default_variant.label')
        }));

        this.grid.collection.each(function(model) {
            if (model.attributes.isVariant) {
                this.defaultProductVariantField.append($('<option>', {
                    value: model.id,
                    text: model.attributes.productName,
                    selected: model.id === this.selectedDefaultVariant
                }));
            }
        }, this);

        this.defaultProductVariantField.trigger('change');
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.grid.collection.off('backgrid:selected');
    }
});

export default {
    /**
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     */
    init: function(deferred, options) {
        options.gridPromise.done(function(grid) {
            const validation = new ProductVariantsEdit({
                grid: grid,
                options: options
            });
            deferred.resolve(validation);
        }).fail(function() {
            deferred.reject();
        });
    }
};
