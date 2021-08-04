define([
    'underscore',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/delete-confirmation',
    'oro/datagrid/action/delete-action'
], function(_, messenger, __, DeleteConfirmation, DeleteAction) {
    'use strict';

    /**
     * Delete action with confirm dialog, triggers REST DELETE request
     *
     * @export  oro/datagrid/action/payment_delete-action
     * @class   oro.datagrid.action.PaymentDeleteAction
     * @extends oro.datagrid.action.DeleteAction
     */
    const PaymentDeleteAction = DeleteAction.extend({

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        /** @property {String} */
        confirm_content: __('Are you sure you want to delete this item?'),

        /**
         * @inheritdoc
         */
        constructor: function PaymentDeleteAction(options) {
            PaymentDeleteAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (this.model.has('payment_delete_message')) {
                this.confirm_content = this.model.get('payment_delete_message');
            }

            DeleteAction.__super__.initialize.call(this, options);
        },

        getConfirmDialogOptions: function() {
            const options = {
                title: __(this.messages.confirm_title),
                content: this.confirm_content,
                okText: __(this.messages.confirm_ok),
                cancelText: __(this.messages.confirm_cancel)
            };

            return options;
        }
    });

    return PaymentDeleteAction;
});
