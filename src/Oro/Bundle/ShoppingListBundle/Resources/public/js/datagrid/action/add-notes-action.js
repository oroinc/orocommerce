define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const ModelAction = require('oro/datagrid/action/model-action');
    const DialogWidget = require('orofrontend/js/app/components/frontend-dialog-widget');
    const template = require('tpl-loader!oroshoppinglist/templates/line-item-notes-dialog.html');

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

        /**
         * @inheritDoc
         */
        _handleWidget: function() {
            if (this.dispatched) {
                return;
            }

            this.frontend_options = this.frontend_options || {};
            this.frontend_options.title = this.model.get('name');

            this.formWidget = new DialogWidget(this.frontend_options);
            this.formWidget.setContent(template({note: this.model.get('note')}));

            this.listenToOnce(this.formWidget, {
                'frontend-dialog:accept': this._handleAjax.bind(this)
            });
        },

        /**
         * @inheritDoc
         */
        getActionParameters: function() {
            const params = AddNotesAction.__super__.getActionParameters.call(this);
            params.notes = this.formWidget.find('textarea[data-role="notes"]').val();

            return JSON.stringify(params);
        },

        _showAjaxSuccessMessage: function() {
            mediator.execute(
                'showFlashMessage',
                'success',
                __('oro.frontend.shoppinglist.lineitem.dialog.note.success')
            );
        }
    });

    return AddNotesAction;
});
