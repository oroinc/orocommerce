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
         * @property {Object}
         */
        accountForm: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.accountForm = this.options._sourceElement.find(this.options.accountFormId);
            this.accountForm.on('change', _.bind(this.reloadRoleWidget, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.accountForm.off('change');
            AccountUser.__super__.dispose.call(this);
        },

        reloadRoleWidget: function(e) {
            var accountUserId = this.options.accountUserId;
            var accountId = e.target.value;

            widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
                var url;
                if (accountUserId) {
                    url = routing.generate('orob2b_account_account_user_get_roles_with_user', {
                        accountId: accountId,
                        accountUserId: accountUserId
                    });
                } else {
                    url = routing.generate('orob2b_account_account_user_by_account_roles', {
                        accountId: accountId
                    });
                }

                widget.setUrl(url);
                widget.render();
            });
        }
    });

    return AccountUser;
});
