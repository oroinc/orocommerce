import $ from 'jquery';
import _ from 'underscore';

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

        this.grid.collection.on('backgrid:selected', this.updateDefaultVariantSelector.bind(this));
    },

    updateDefaultVariantSelector: function(model, selected) {
        if (this.defaultProductVariantField.length !== 1) {
            return;
        }

        const currentVal = this.defaultProductVariantField.val();

        // Remove existing option first to avoid duplicates when navigating between grid pages,
        // then re-add it only if the variant is selected.
        this.defaultProductVariantField.find('option[value="' + model.id + '"]').remove();
        if (selected) {
            this.defaultProductVariantField.append($('<option>', {
                value: model.id,
                text: model.get('productName')
            }));
        }

        // Restore selection if the removed-and-re-added option was previously selected
        this.defaultProductVariantField.val(currentVal);
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
