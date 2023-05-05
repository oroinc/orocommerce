import {uniq, difference} from 'underscore';
import __ from 'orotranslation/js/translator';
import routing from 'routing';
import BaseComponent from 'oroui/js/app/components/base/component';
import ProductKitItemsView from 'oroproduct/js/app/views/product-kit/product-kit-items-view';
import DialogWidget from 'oro/dialog-widget';
import DeleteConfirmation from 'oroui/js/delete-confirmation';

const ProductKitItemsComponent = BaseComponent.extend({
    relatedSiblingComponents: {
        gridComponent: 'related-products-grid-name'
    },

    listen() {
        return {
            [`kit-item-products-remove:${this.gridComponent.grid.getGridScope()} mediator`]: 'removeProductKitItem'
        };
    },

    /**
     * @inheritdoc
     */
    constructor: function ProductKitItemsComponent(options) {
        ProductKitItemsComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        ProductKitItemsComponent.__super__.initialize.call(this, options);

        if (!this.gridComponent) {
            const gridComponentName = options.relatedSiblingComponents.__initial__.gridComponent;
            throw new Error(`GridComponent with name "${gridComponentName}" is not found, ` +
                'it is required for ProductKitItems functionality.');
        }

        Object.assign(this, {
            addProductDialogOptions: {
                route: 'oro_datagrid_widget',
                routeParams: {
                    gridName: 'kit-item-products-add-grid'
                },
                ...options.addProductDialogOptions
            }
        });

        this.initView(options);
    },

    /**
     * @inheritdoc
     */
    delegateListeners() {
        ProductKitItemsComponent.__super__.delegateListeners.call(this);

        this.listenTo(this.view, {
            'add-product-button-click': this.addProductKitItem
        });
    },

    /**
     * Initializes view for a source element
     * @param {Object} options
     * @param {jQuery.Element} options._sourceElement
     * @param {Object} options.viewOptions
     */
    initView({_sourceElement, viewOptions}) {
        this.view = new ProductKitItemsView({
            ...viewOptions,
            el: _sourceElement,
            collection: this.gridComponent.collection
        });
    },

    /**
     * Opens dialog for product selection.
     * Handles `'grid-row-select'` event and add selected product to kit collection
     */
    addProductKitItem() {
        const {collection} = this.gridComponent;
        const {route, routeParams} = this.addProductDialogOptions;

        const dialog = new DialogWidget({
            autoRender: true,
            title: __('oro.product.product_kit.dialog.select.title'),
            url: routing.generate(route, {
                ...routeParams,
                params: {
                    selectedProductsIds: collection.map(model => model.get('id'))
                }
            }),
            dialogOptions: {
                modal: true
            }
        });

        this.listenToOnce(dialog, {
            'grid-row-select': ({model}) => {
                const {collection} = this.gridComponent;
                const selectedProductsIds = collection.map(model => String(model.get('id')));
                collection.assignUrlParams({
                    selectedProductsIds: uniq(selectedProductsIds.concat(model.get('id')))
                });

                collection.fetch({reset: true});
                dialog.remove();
            },
            'dispose': () => this.stopListening(dialog)
        });
    },

    /**
     * Remove product from kit collection
     * @param {Array.<string>} ids
     */
    removeProductKitItem(ids) {
        const confirm = new DeleteConfirmation({
            title: __('oro.product.product_kit.confirmation.title'),
            content: __('oro.product.product_kit.confirmation.desc')
        });

        confirm.on('ok', () => {
            const {collection} = this.gridComponent;
            const selectedProductsIds = collection.map(model => String(model.get('id')));
            collection.urlParams.selectedProductsIds = difference(selectedProductsIds, ids.map(String));

            const models = ids.map(id => collection.get(id));
            collection.remove(models, {alreadySynced: true});
        });
        confirm.open();
    }
});

export default ProductKitItemsComponent;
