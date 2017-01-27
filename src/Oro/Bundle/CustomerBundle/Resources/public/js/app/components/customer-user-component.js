/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var CustomerUser;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var routing = require('routing');
    var widgetManager = require('oroui/js/widget-manager');

    CustomerUser = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            widgetAlias: null,
            customerFormId: null,
            customerUserId: null
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.options._sourceElement
                .on('change', this.options.customerFormId, _.bind(this.reloadRoleWidget, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off('change');
            CustomerUser.__super__.dispose.call(this);
        },

        /**
         * Reload widget with roles
         *
         * @param {Event} e
         */
        reloadRoleWidget: function(e) {
            var customerUserId = this.options.customerUserId;
            var customerId = e.target.value;

            widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
                var params = {customerId: customerId};
                if (customerUserId) {
                    params.customerUserId = customerUserId;
                }

                widget.setUrl(
                    routing.generate('oro_customer_customer_user_roles', params)
                );
                widget.render();
            });
        }
    });

    return CustomerUser;
});
