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
                .on('click', 'a', _.bind(this.onClick, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            e.preventDefault();
            var $element = $(e.target);
            mediator.execute('showLoading');
            if ($element.data('dialog-url')) {
                require(['oro/dialog-widget'],
                    function(DialogWidget) {
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
                        var formWidget = new DialogWidget(dialogOptions);
                        formWidget.on('formSave', function(data) {
                            formWidget.remove();
                        });
                        formWidget.render();
                        mediator.execute('hideLoading');
                    }
                );
            } else {
                $.getJSON(e.target.href)
                    .done(_.bind(function(response) {
                        mediator.execute('hideLoading');

                        if (response.redirectUrl) {
                            e.stopImmediatePropagation();
                            this.doRedirect(response.redirectUrl);
                        } else {
                            this.doPageReload();
                        }
                    }, this))
                    .fail(function() {
                        mediator.execute('hideLoading');
                        messenger.notificationFlashMessage('error', __('Could not perform action'));
                    });
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
