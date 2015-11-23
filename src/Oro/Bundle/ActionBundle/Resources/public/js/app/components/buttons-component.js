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
            this.$container.find('a').on('click', _.bind(this.onClick, this));
        },

        onClick: function(e) {
            e.preventDefault();

            mediator.execute('showLoading');
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

            ButtonsComponent.__super__.dispose.call(this);
        }
    });

    return ButtonsComponent;
});
