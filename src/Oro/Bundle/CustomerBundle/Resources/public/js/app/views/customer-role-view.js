define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'orouser/js/views/role-view'
], function($, _, mediator, RoleView) {
    'use strict';

    var CustomerRoleView;

    /**
     * @export orocustomer/js/app/views/customer-role-view
     */
    CustomerRoleView = RoleView.extend({
        options: {
            customerSelector: ''
        },

        /**
         * Initialize
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            CustomerRoleView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        getData: function() {
            var data = CustomerRoleView.__super__.getData.apply(this, arguments);

            data[this.options.formName + '[customer]'] = $(this.options.customerSelector).inputWidget('val');

            return data;
        }
    });

    return CustomerRoleView;
});
