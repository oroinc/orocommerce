define([
    'oro/datagrid/action/mass-action',
    'oroui/js/mediator',
    'oroui/js/standart-confirmation',
    'underscore',
    'orotranslation/js/translator'
], function(MassAction, mediator, StandardConfirmation, _, __) {
    'use strict';

    var GetSelectedProductIdsMassAction;

    GetSelectedProductIdsMassAction = MassAction.extend({

        /**
         * @param {Boolean}
         */
        force: false,

        /**
         * @param {StandardConfirmation}
         */
        forcedConfirmDialog: null,

        /**
         * @param {String}
         */
        eventName: null,

        /**
         * @inheritdoc
         */
        initialize: function() {
            GetSelectedProductIdsMassAction.__super__.initialize.apply(this, arguments);
            this.eventName = this.datagrid.toolbarOptions.selectedProducts.eventName;
            mediator.on('get-selected-products-mass-action-run', this.runAction, this);
        },

        runAction: function() {
            this.run({});
        },

        /**
         * @inheritdoc
         */
        execute: function() {
            var selectionState = this.datagrid.getSelectionState();
            if (selectionState.inset) {
                this._triggerSelectEvent(selectionState.selectedIds);
            } else {
                this.datagrid.showLoading();
                MassAction.__super__.execute.call(this);
            }
        },

        /**
         * @inheritdoc
         */
        getActionParameters: function() {
            var params = GetSelectedProductIdsMassAction.__super__.getActionParameters.call(this);
            params.force = +this.force;

            return params;
        },

        /**
         * @param {Array} ids
         * @private
         */
        _triggerSelectEvent: function(ids) {
            mediator.trigger(this.eventName, ids);
        },

        /**
         * @param {Object} data
         * @private
         */
        _onAjaxSuccess: function(data) {
            this.datagrid.hideLoading();
            if (data.successful) {
                this.force = false;
                this._triggerSelectEvent(data.ids);
            } else {
                this._getForcedConfirmDialog(data.message).open();
            }
        },

        /**
         * @param {String} message
         * @return {StandardConfirmation}
         * @private
         */
        _getForcedConfirmDialog: function(message) {
            if (!this.forcedConfirmDialog) {
                this.forcedConfirmDialog = new StandardConfirmation({content: __(message)});
                this.forcedConfirmDialog.on('ok', _.bind(this.onForcedConfirmDialogOk, this));
            }

            return this.forcedConfirmDialog;
        },

        /**
         * @private
         */
        onForcedConfirmDialogOk: function() {
            this.force = true;
            this.execute();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.forcedConfirmDialog;

            GetSelectedProductIdsMassAction.__super__.dispose.call(this);

            mediator.off(null, null, this);
        }
    });

    return GetSelectedProductIdsMassAction;
});
