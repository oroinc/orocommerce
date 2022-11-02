define([
    'oro/datagrid/action/mass-action',
    'oroui/js/mediator',
    'oroui/js/standart-confirmation',
    'underscore'
], function(MassAction, mediator, StandardConfirmation, _) {
    'use strict';

    const TriggerEventForSelectedProductIdsMassAction = MassAction.extend({

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
        initialize: function(options) {
            TriggerEventForSelectedProductIdsMassAction.__super__.initialize.call(this, options);
            mediator.on('get-selected-products-mass-action-run:' + this.datagrid.name, this.runAction, this);
        },

        runAction: function() {
            this.run({});
        },

        /**
         * @inheritdoc
         */
        execute: function() {
            const selectionState = this.datagrid.getSelectionState();

            if (!this.checkSelectionState()) {
                return;
            }

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
            const params = TriggerEventForSelectedProductIdsMassAction.__super__.getActionParameters.call(this);
            params.force = +this.force;

            return params;
        },

        /**
         * @param {Array} ids
         * @private
         */
        _triggerSelectEvent: function(ids) {
            const scope = this.datagrid.getGridScope();
            if (scope) {
                mediator.trigger(this.event_name + ':' + scope, ids);
            }
            mediator.trigger(this.event_name, ids);

            if (this.datagrid) {
                this.datagrid.resetSelectionState();
            }
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
                this.forcedConfirmDialog = new StandardConfirmation({content: message});
                this.forcedConfirmDialog.on('ok', this.onForcedConfirmDialogOk.bind(this));
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
         * @inheritdoc
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
