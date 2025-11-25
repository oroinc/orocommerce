import routing from 'routing';
import DialogAction from 'oro/datagrid/action/dialog-action';

/**
 * Update Product Kit action with configuration dialog
 *
 * @export  oro/datagrid/action/update-product-kit-line-item-action
 * @class   oro.datagrid.action.UpdateProductKitLineItemAction
 * @extends oro.datagrid.action.DialogAction
 */
const UpdateProductKitLineItemAction = DialogAction.extend({
    widgetDefaultOptions: {
        type: 'product-kit-line-item',
        multiple: false
    },

    /**
     * @inheritdoc
     */
    constructor: function UpdateProductKitLineItemAction(options) {
        UpdateProductKitLineItemAction.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    run: function() {
        this.model.set('line_item_form_enable', true);
        this.widgetOptions.options.model = this.model;
        this.widgetOptions.options.initLayoutOptions = {
            productModel: this.model
        };
        this.widgetOptions.options.url = this.getLink();
        this.widgetOptions.options.widgetData = {
            savedForLaterGrid: this.model.collection.options?.savedForLaterGrid ?? false
        };

        UpdateProductKitLineItemAction.__super__.run.call(this);
    },

    /**
     * @inheritdoc
     */
    getLink: function() {
        return routing.generate('oro_shopping_list_frontend_product_kit_line_item_update', {
            id: this.model.attributes.id
        });
    }
});

export default UpdateProductKitLineItemAction;

