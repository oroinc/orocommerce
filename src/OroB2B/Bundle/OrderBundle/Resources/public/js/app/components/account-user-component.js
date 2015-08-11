define(function(require) {
    'use strict';

    var AccountUserComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2border/js/app/components/account-user-component
     * @extends oroui.app.components.base.Component
     * @class orob2border.app.components.AccountUserComponent
     */
    AccountUserComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            relatedDataRoute: 'orob2b_order_related_data',
            selectors: {
                account: ''
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $select: null,

        /**
         * @property {jQuery}
         */
        $account: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, this.options, options || {});

            this.$el = options._sourceElement;
            this.$select = this.$el.find('select');
            this.$account = $(this.options.selectors.account);

            this.$select.change(_.bind(this.userChanged, this));
        },

        userChanged: function() {
            var url = routing.generate(this.options.relatedDataRoute, {
                accountId: this.$account.val(),
                accountUserId: this.$select.val()
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
        }
    });

    return AccountUserComponent;
});
