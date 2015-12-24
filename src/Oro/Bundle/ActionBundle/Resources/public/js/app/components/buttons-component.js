/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ButtonsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var DialogWidget = require('oro/dialog-widget');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');

    ButtonsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @property {jQuery.Element}
         */
        $container: {},

        messages: {
            confirm_title: 'oro.action.confirm_title',
            confirm_content: 'oro.action.confirm_content',
            confirm_ok: 'Yes',
            confirm_cancel: 'Cancel'
        },

        /** @param {Object} */
        confirmModal: null,

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$container = $(this.options._sourceElement);
            this.$container
                .on('click', 'a.action-button', _.bind(this.onClick, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            e.preventDefault();
            var $element = $(e.currentTarget);
            if ($element.data('confirmation')) {
                this.messages.confirm_content = $element.data('confirmation');
                this.getConfirmDialog(_.bind(this.doExecute, this, e, $element)).open();
            } else {
                this.doExecute(e, $element);
            }
        },

        /**
         * @param {jQuery.Element} $element
         * @return {Object}
         * @private
         */
        _getDialogOptions: function($element) {
            var dialogOptions = {
                title: 'action',
                url: $element.data('dialog-url'),
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

            var additionalOptions = $element.data('dialog-options');
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
         * @param {jQuery.Event} e
         * @param {jQuery.Element} $element
         */
        doExecute: function(e, $element) {
            if ($element.data('dialog-url')) {
                var widget = new DialogWidget(this._getDialogOptions($element));

                this.listenTo(widget, 'formSave', _.bind(function(response) {
                    widget.remove();
                    this.doResponse(e, response);
                }, this));

                widget.render();
            } else {
                mediator.execute('showLoading');

                $.getJSON($element.attr('href'))
                    .done(_.bind(function(response) {
                        this.doResponse(e, response);
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
        },

        /**
         * @param {jQuery.Event} e
         * @param {Object} response
         */
        doResponse: function(e, response) {
            mediator.execute('hideLoading');

            if (response.flashMessages) {
                _.each(response.flashMessages, function(messages, type) {
                    _.each(messages, function(message) {
                        messenger.notificationFlashMessage(type, message);
                    });
                });
            }

            if (response.redirectUrl) {
                e.stopImmediatePropagation();
                this.doRedirect(response.redirectUrl);
            } else if (response.refreshGrid) {
                _.each(response.refreshGrid, function(gridname) {
                    mediator.trigger('datagrid:doRefresh:' + gridname);
                });
            } else {
                this.doPageReload();
            }
        },

        /**
         * @param {String} redirectUrl
         */
        doRedirect: function(redirectUrl) {
            mediator.execute('redirectTo', {url: redirectUrl});
        },

        doPageReload: function() {
            mediator.execute('refreshPage');
        },

        /**
         * Get view for confirm modal
         *
         * @return {oroui.Modal}
         */
        getConfirmDialog: function(callback) {
            if (!this.confirmModal) {
                this.confirmModal = (new this.confirmModalConstructor({
                    title: __(this.messages.confirm_title),
                    content: __(this.messages.confirm_content),
                    okText: __(this.messages.confirm_ok),
                    cancelText: __(this.messages.confirm_cancel)
                }));
                this.listenTo(this.confirmModal, 'ok', callback);
            } else {
                this.confirmModal.setContent(__(this.messages.confirm_content));
            }

            return this.confirmModal;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$container.off();
            delete this.confirmModal;

            ButtonsComponent.__super__.dispose.call(this);
        }
    });

    return ButtonsComponent;
});
