/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AccountUser;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var routing = require('routing');
    var widgetManager = require('oroui/js/widget-manager');

    AccountUser = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            widgetAlias: null,
            accountFormId: null,
            accountUserId: null
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.options._sourceElement
                .on('change', this.options.accountFormId, _.bind(this.reloadRoleWidget, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off('change');
            AccountUser.__super__.dispose.call(this);
        },

        /**
         * Reload widget with roles
         *
         * @param {Event} e
         */
        reloadRoleWidget: function(e) {
            var accountUserId = this.options.accountUserId;
            var accountId = e.target.value;

            widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
                var params = {accountId: accountId};
                if (accountUserId) {
                    params.accountUserId = accountUserId;
                }

                widget.setUrl(
                    routing.generate('orob2b_account_account_user_roles', params)
                );
                widget.render();
            });
        }
    });

    return AccountUser;
});
