define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'orouser/js/views/role-view'
], function($, _, mediator, RoleView) {
    'use strict';

    var AccountRoleView;

    /**
     * @export orob2baccount/js/app/views/account-role-view
     */
    AccountRoleView = RoleView.extend({
        options: {
            accountSelector: ''
        },

        /**
         * Initialize
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            AccountRoleView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        getData: function() {
            var data = AccountRoleView.__super__.getData.apply(this, arguments);

            data[this.options.formName + '[account]'] = $(this.options.accountSelector).inputWidget('val');

            return data;
        }
    });

    return AccountRoleView;
});
