define(function(require) {
    'use strict';

    var RelatedDataComponent;
    var $ = require('jquery');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2border/js/app/components/related-data-component
     * @extends oroui.app.components.base.Component
     * @class orob2border.app.components.RelatedDataComponent
     */
    RelatedDataComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            relatedDataRoute: 'orob2b_order_related_data'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, this.options, options || {});

            mediator.on('account-account-user:change', this.loadRelatedData, this);
        },

        /**
         * Load related to user data and trigger event
         */
        loadRelatedData: function(accountUser) {
            var url = routing.generate(this.options.relatedDataRoute, {
                accountId: accountUser.accountId,
                accountUserId: accountUser.accountUserId
            });

            mediator.trigger('order:load:related-data');

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    mediator.trigger('order:loaded:related-data', response);
                },
                error: function() {
                    mediator.trigger('order:loaded:related-data', {});
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

            mediator.off('account-account-user:change', this.loadRelatedData, this);

            RelatedDataComponent.__super__.dispose.call(this);
        }
    });

    return RelatedDataComponent;
});
