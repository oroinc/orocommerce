import ModelAction from 'oro/datagrid/action/model-action';

const OrderLineItemDraftUpdateAction = ModelAction.extend({
    useDirectLauncherLink: false,

    /**
     * @inheritDoc
     */
    constructor: function OrderLineItemDraftUpdateAction(options) {
        OrderLineItemDraftUpdateAction.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    run() {
        const row = this.datagrid.body.rows.find(
            row => row.model.get('orderLineItemId') === this.model.get('orderLineItemId')
        );

        if (!row) {
            console.error('Row element not found for model id:', this.model.get('orderLineItemId'));
            return;
        }

        if (!row.disposed) {
            row.trigger('render:edit-mode', {
                editMode: true
            });
        }
    }
});
export default OrderLineItemDraftUpdateAction;
