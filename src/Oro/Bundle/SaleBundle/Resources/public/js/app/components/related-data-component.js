define(function(require) {
    'use strict';

    var RelatedDataComponent;
    var $ = require('jquery');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export oroorder/js/app/components/related-data-component
     * @extends oroui.app.components.base.Component
     * @class orob2sale.app.components.RelatedDataComponent
     */
    RelatedDataComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            relatedDataRoute: 'oro_quote_related_data',
            formName: ''
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            mediator.on('customer-customer-user:change', this.onAccountUserChange, this);
        },

        /**
         * @param accountUser
         */
        onAccountUserChange: function(accountUser) {
            this.loadRelatedData(accountUser);

            mediator.trigger('entry-point:quote:trigger');
        },

        /**
         * Load related to user data and trigger event
         */
        loadRelatedData: function(customerUser) {
            var url = routing.generate(this.options.relatedDataRoute);
            var data = {
                customer: customerUser.customerId,
                customerUser: customerUser.customerUserId
            };

            var ajaxData = {};
            if (this.options.formName) {
                ajaxData[this.options.formName] = data;
            } else {
                ajaxData = data;
            }

            mediator.trigger('quote:load:related-data');

            $.ajax({
                url: url,
                type: 'GET',
                data: ajaxData,
                success: function(response) {
                    mediator.trigger('quote:loaded:related-data', response);
                },
                error: function() {
                    mediator.trigger('quote:loaded:related-data', {});
                }
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('customer-customer-user:change', this.loadRelatedData, this);

            RelatedDataComponent.__super__.dispose.call(this);
        }
    });

    return RelatedDataComponent;
});
