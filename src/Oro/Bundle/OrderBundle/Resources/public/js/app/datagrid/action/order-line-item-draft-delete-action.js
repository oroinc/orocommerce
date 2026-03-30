import $ from 'jquery';
import _ from 'underscore';
import messenger from 'oroui/js/messenger';
import __ from 'orotranslation/js/translator';
import DeleteConfirmation from 'oroui/js/delete-confirmation';
import mediator from 'oroui/js/mediator';
import DeleteAction from 'oro/datagrid/action/delete-action';

/**
 * Delete action for draft order line items
 *
 * @export  oro/datagrid/action/order-line-item-draft-delete-action
 * @class   oro.datagrid.action.OrderLineItemDraftDeleteAction
 * @extends oro.datagrid.action.ModelAction
 */
const OrderLineItemDraftDeleteAction = DeleteAction.extend({
    /** @property {Function} */
    confirmModalConstructor: DeleteConfirmation,

    /**
     * @inheritDoc
     */
    constructor: function OrderLineItemDraftDeleteAction(options) {
        OrderLineItemDraftDeleteAction.__super__.constructor.call(this, options);
    },

    /**
     * Confirm delete item
     */
    doDelete(messages) {
        const url = this.getLink();

        mediator.execute('showLoading');

        if (this.model.get('editMode') && this.model.get('fieldChanged')) {
            this.model.set('fieldChanged', false);
        }

        $.ajax({
            url: url,
            method: 'DELETE',
            dataType: 'json',
            success: response => {
                if (response.successful) {
                    messenger.notificationFlashMessage('success', __(messages.success));

                    // Process widget triggers from response
                    // This refreshes the datagrid from server to show actual state
                    if (response.widget && response.widget.trigger) {
                        _.each(response.widget.trigger, function(trigger) {
                            if (trigger.eventBroker === 'mediator') {
                                mediator.trigger(trigger.name, trigger.args);
                            }
                        });
                    }

                    mediator.trigger('entry-point:order:trigger');
                }
            },
            complete: () => {
                mediator.execute('hideLoading');
            }
        });
    }
});
export default OrderLineItemDraftDeleteAction;
