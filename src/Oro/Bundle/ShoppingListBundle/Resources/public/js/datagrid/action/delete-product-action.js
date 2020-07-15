define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'oro/datagrid/action/delete-action'
], function(_, __, messenger, DeleteAction) {
    'use strict';

    /**
     * Delete action with confirm dialog, triggers REST DELETE request
     *
     * @export  oro/datagrid/action/delete-product-action
     * @class   oro.datagrid.action.DeleteProductAction
     * @extends oro.datagrid.action.DeleteAction
     */
    const DeleteProductAction = DeleteAction.extend({
        /**
         * @inheritDoc
         */
        constructor: function DeleteProductAction(options) {
            DeleteProductAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        getConfirmDialogOptions: function() {
            return {
                title: this.getConfirmContentTitle(),
                content: this.getConfirmContentMessage(),
                okText: __(this.messages.confirm_ok),
                cancelText: __(this.messages.confirm_cancel)
            };
        },

        /**
         * Get confirm content title
         *
         * @return {String}
         */
        getConfirmContentTitle: function() {
            return __(this.messages.confirm_title, this.model.toJSON());
        },

        /**
         * @inheritDoc
         */
        doDelete: function() {
            const success = __(this.messages.success, this.model.toJSON());

            this.model.destroy({
                url: this.getLink(),
                wait: true,
                success: function() {
                    messenger.notificationFlashMessage('success', success);
                }
            });
        }
    });

    return DeleteProductAction;
});
