import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';

const ProductKitItemsView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['originField', 'collection']),

    /**
     * @property {string} originField selector for a hidden input
     */
    originField: void 0,

    /**
     * @property {Backbone.Collection} collection datagrid collection
     */
    collection: null,

    events: {
        'click .product-kit-item__add-product-button': 'onAddProductButtonClick'
    },

    listen: {
        'sync collection': 'handleCollectionChanges',
        'remove collection': 'handleCollectionChanges',
        'change:sortOrder collection': 'handleCollectionChanges'
    },

    constructor: function ProductKitItemsView(...args) {
        ProductKitItemsView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        ProductKitItemsView.__super__.initialize.call(this, options);
        this.updateValueToShow();
    },

    /**
     * Re-triggers Backbone event once AddProduct button click occurs
     */
    onAddProductButtonClick() {
        this.trigger('add-product-button-click');
    },

    /**
     * Handles collection changes (sync, remove or sortOrder property change events)
     */
    handleCollectionChanges() {
        this.updateValueToShow();
        this.updateOriginField();
    },

    /**
     * Update origin field from product collection data
     */
    updateOriginField() {
        const value = this.collection.map(model => ({
            productId: String(model.get('id')),
            sortOrder: Number(model.get('sortOrder'))
        }));

        this.$(this.originField)
            .val(JSON.stringify(value))
            .trigger('change');
    },

    /**
     * Generate sku's string from product collection
     */
    updateValueToShow() {
        const {collection} = this;
        const valueToShow = collection.length ? collection.map(model => model.get('sku')).join(', ') : __('N/A');

        this.$(this.originField)
            .data('formatted-value', valueToShow)
            .trigger('change');
    }
});

export default ProductKitItemsView;
