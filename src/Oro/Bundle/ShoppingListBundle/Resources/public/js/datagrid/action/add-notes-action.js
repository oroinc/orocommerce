define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const ModelAction = require('oro/datagrid/action/model-action');
    const Modal = require('oroui/js/modal');
    /**
     * Add notes action, triggers REST PATCH request
     *
     * @export  oro/datagrid/action/add-notes-action
     * @class   oro.datagrid.action.AddNotesAction
     * @extends oro.datagrid.action.ModelAction
     */
    const AddNotesAction = ModelAction.extend({
        /**
         * @property {Object}
         */
        formWidget: null,

        requestType: 'PATCH',

        reloadData: false,

        /**
         * @inheritDoc
         */
        constructor: function AddNotesAction(options) {
            AddNotesAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        getLink: function() {
            return routing.generate(this.route, {id: this.model.get('id'), ...this.route_parameters});
        },

        _onAjaxSuccess: function(data) {
            this.model.set({notes: data.fields.notes});
            this._showAjaxSuccessMessage(data);
        },

        /**
         * @inheritDoc
         */
        _handleWidget: function() {
            if (this.dispatched) {
                return;
            }

            const notes = this.model.get('notes');
            const action = notes ? 'edit' : 'add';

            const modal = new Modal({
                className: 'modal oro-modal-normal shopping-list-notes-modal',
                title: __(`oro.frontend.shoppinglist.lineitem.dialog.${action}.title`, {
                    productName: this.model.get('name')
                }),
                okText: __(`oro.frontend.shoppinglist.lineitem.dialog.${action}.label`),
                cancelText: __('oro.frontend.shoppinglist.lineitem.dialog.cancel.label'),
                content: '<textarea class="textarea full shopping-list-notes-modal__editor"' +
                    ' data-autoresize data-role="notes"></textarea>'
            });

            modal.on('ok', () => {
                this.model.set('notes', modal.$('[data-role="notes"]').val());
                this._handleAjax();
            });

            modal.open();

            modal.$('[data-role="notes"]').focus().val(notes).click();
        },

        /**
         * @inheritDoc
         */
        getActionParameters: function() {
            const params = AddNotesAction.__super__.getActionParameters.call(this);
            params.notes = this.model.get('notes');

            return JSON.stringify(params);
        },

        _showAjaxSuccessMessage: function() {
            mediator.execute(
                'showFlashMessage',
                'success',
                __('oro.frontend.shoppinglist.lineitem.dialog.notes.success')
            );
        }
    });

    return AddNotesAction;
});
