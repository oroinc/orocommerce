define(function(require) {
    'use strict';

    const $ = require('jquery');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export oroorder/js/app/components/related-data-component
     * @extends oroui.app.components.base.Component
     * @class orob2sale.app.components.RelatedDataComponent
     */
    const RelatedDataComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            relatedDataRoute: 'oro_quote_related_data',
            formName: ''
        },

        /**
         * @inheritdoc
         */
        constructor: function RelatedDataComponent(options) {
            RelatedDataComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
            const url = routing.generate(this.options.relatedDataRoute);
            const data = {
                customer: customerUser.customerId,
                customerUser: customerUser.customerUserId
            };

            let ajaxData = {};
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
         * @inheritdoc
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
