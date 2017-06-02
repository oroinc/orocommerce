define([
    'oro/datagrid/action/mass-action',
    'oroui/js/mediator',
    'oroui/js/standart-confirmation',
    'underscore',
    'orotranslation/js/translator'
], function(MassAction, mediator, StandardConfirmation, _, __) {
    'use strict';

    var TriggerEventForSelectedProductIdsMassAction;

    TriggerEventForSelectedProductIdsMassAction = MassAction.extend({

        /**
         * @param {Boolean}
         */
        force: false,

        /**
         * @param {StandardConfirmation}
         */
        forcedConfirmDialog: null,

        /**
         * @inheritdoc
         */
        initialize: function() {
            TriggerEventForSelectedProductIdsMassAction.__super__.initialize.apply(this, arguments);
            mediator.on('get-selected-products-mass-action-run:' + this.datagrid.name, this.runAction, this);
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
            var params = TriggerEventForSelectedProductIdsMassAction.__super__.getActionParameters.call(this);
            params.force = +this.force;

            return params;
        },

        /**
         * @param {Array} ids
         * @private
         */
        _triggerSelectEvent: function(ids) {
            var scope = this.datagrid.getGridScope();
            if (scope) {
                mediator.trigger(this.event_name + ':' + scope, ids);
            }
            mediator.trigger(this.event_name, ids);
            this.datagrid.resetSelectionState();
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

            TriggerEventForSelectedProductIdsMassAction.__super__.dispose.call(this);

            mediator.off(null, null, this);
        }
    });

    return TriggerEventForSelectedProductIdsMassAction;
});
