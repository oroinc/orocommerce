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
        'type': 'dialog',
        'multiple': false,
        'reload-grid-name': '',
        'options': {
            dialogOptions: {
                allowMaximize: false,
                allowMinimize: false,
                modal: true,
                resizable: false,
                maximizedHeightDecreaseBy: 'minimize-bar',
                width: 800
            },
            fullscreenViewOptions: {}
        }
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
        this.widgetOptions.model = this.model;
        this.widgetOptions.options.initLayoutOptions = {
            productModel: this.model
        };
        this.widgetOptions.url = this.getLink();

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

