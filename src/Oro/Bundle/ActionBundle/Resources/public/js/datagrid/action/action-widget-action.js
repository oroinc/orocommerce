/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ActionWidgetAction;

    var ModelAction = require('oro/datagrid/action/model-action');
    var ActionManager = require('oroaction/js/action-manager');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var routing = require('routing');
    var Backbone = require('backbone');
    var DialogWidget = require('oro/dialog-widget');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');

    /**
     * @export oro/datagrid/action/action-widget-action
     * @class oro.datagrid.action.ActionWidgetAction
     * @extends oro.datagrid.action.ModelAction
     */
    ActionWidgetAction = ModelAction.extend({
        options: {
            datagrid: null,
            confirmation: null,
            showDialog: null,
            executionRoute: null,
            dialogRoute: null,
            dialogOptions: {
                title: 'Action',
                allowMaximize: false,
                allowMinimize: false,
                modal: true,
                resizable: false,
                maximizedHeightDecreaseBy: 'minimize-bar',
                width: 550
            }
        },

        defaultMessages: {
            confirm_title: 'oro.action.confirm_title',
            confirm_content: 'oro.action.confirm_content',
            confirm_ok: 'Yes',
            confirm_cancel: 'Cancel'
        },

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        /**
         * @param {String} dialogUrl
         * @returns {Object}
         * @private
         */
        _getDialogOptions: function(dialogUrl) {
            var dialogOptions = {
                title: 'action',
                url: dialogUrl,
                stateEnabled: false,
                incrementalPosition: false,
                loadingMaskEnabled: true,
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    width: 475,
                    autoResize: true
                }
            };

            var additionalOptions = this.options.dialogOptions;
            if (additionalOptions) {
                if (additionalOptions.dialogOptions !== undefined) {
                    additionalOptions.dialogOptions = _.extend(
                        dialogOptions.dialogOptions,
                        additionalOptions.dialogOptions
                    );
                }
                dialogOptions = _.extend(dialogOptions, additionalOptions);
            }

            return dialogOptions;
        },

        /**
         * @return {Object}
         */
        _getRouteParams: function() {
            var entityId = this.model[this.model.idAttribute];

            return {
                'actionName': this.options.actionName,
                'entityId': entityId,
                'entityClass': this.options.entityClass,
                'datagrid': this.options.datagrid
            };
        },

        /**
         * @inheritdoc
         */
        run: function() {
            if (this.options.confirmation) {
                this.messages.confirm_content = this.options.confirmation;
                this.getConfirmDialog(_.bind(this.doRun, this)).open();
            } else {
                this.doRun();
            }
        },

        /**
         * @inheritDoc
         */
        doRun: function() {
            var routeParams = this._getRouteParams();
            if (this.options.showDialog) {
                var dialogUrl = routing.generate(this.options.dialogRoute, routeParams);
                var widget = new DialogWidget(this._getDialogOptions(dialogUrl));

                Backbone.listenTo(widget, 'formSave', _.bind(function(response) {
                    widget.remove();
                    ActionManager.doResponse(response);
                }, this));

                widget.render();
            } else {
                mediator.execute('showLoading');
                var url = routing.generate(this.options.executionRoute, routeParams);
                $.getJSON(url)
                    .done(_.bind(function(response) {
                        this.doResponse(response);
                    }, this))
                    .fail(function(jqXHR) {
                        var message = __('Could not perform action');
                        if (jqXHR.statusText) {
                            message += ': ' + jqXHR.statusText;
                        }

                        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                            message += ': ' + jqXHR.responseJSON.message;
                        }

                        mediator.execute('hideLoading');
                        messenger.notificationFlashMessage('error', message);
                    });
            }
        }
    });

    return ActionWidgetAction;
});
