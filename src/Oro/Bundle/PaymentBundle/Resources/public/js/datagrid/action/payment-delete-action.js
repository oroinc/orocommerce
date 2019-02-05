define([
    'underscore',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/delete-confirmation',
    'oro/datagrid/action/delete-action'
], function(_, messenger, __, DeleteConfirmation, DeleteAction) {
    'use strict';

    var PaymentDeleteAction;

    /**
     * Delete action with confirm dialog, triggers REST DELETE request
     *
     * @export  oro/datagrid/action/payment_delete-action
     * @class   oro.datagrid.action.PaymentDeleteAction
     * @extends oro.datagrid.action.DeleteAction
     */
    PaymentDeleteAction = DeleteAction.extend({

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        /** @property {String} */
        confirm_content: __('Are you sure you want to delete this item?'),

        /**
         * @inheritDoc
         */
        constructor: function PaymentDeleteAction() {
            PaymentDeleteAction.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (this.model.has('payment_delete_message')) {
                this.confirm_content = this.model.get('payment_delete_message');
            }

            DeleteAction.__super__.initialize.apply(this, arguments);
        },

        getConfirmDialogOptions: function() {
            var options = {
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
