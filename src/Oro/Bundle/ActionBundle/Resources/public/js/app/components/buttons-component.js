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

    ButtonsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @property {jQuery.Element}
         */
        $container: {},

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
         * @param {jQuery.Element} $element
         * @return {Object}
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
         * @param {Object} response
         */
        doResponse: function(e, response) {
            mediator.execute('hideLoading');

            if (response.flashMessages) {
                for (var message in response.flashMessages) {
                    var type = response.flashMessages[message];
                    messenger.notificationFlashMessage(type[0], message);
                }
            }

            if (response.redirectUrl) {
                e.stopImmediatePropagation();
                this.doRedirect(response.redirectUrl);
            } else if (response.refreshGrid) {
                for (var k in response.refreshGrid) {
                    mediator.trigger('datagrid:doRefresh:' + response.refreshGrid[k]);
                }
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

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$container.off();

            ButtonsComponent.__super__.dispose.call(this);
        }
    });

    return ButtonsComponent;
});
