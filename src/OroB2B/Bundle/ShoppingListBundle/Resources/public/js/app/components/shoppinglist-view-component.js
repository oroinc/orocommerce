define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var _ = require('underscore');
    var $ = require('jquery');

    return BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            eventChannelId: null
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$shoppingListItems = this.options._sourceElement.find('[data-shopping-list-items]');

            mediator.on('shopping-list:created', function() {
                mediator.execute('redirectTo', {
                    url: routing.generate('orob2b_shopping_list_frontend_view')
                }, {
                    redirect: true
                });
            });

            mediator.on('shopping-list-event:' + this.options.eventChannelId + ':update', this.updateDropdown, this);
        },

        /**
         *
         * @param updateData
         */
        updateDropdown: function(updateData) {
            this.$shoppingListItems
                .filter(function() {
                    return $(this).data('id') === updateData.id;
                })
                .children()
                .text(updateData.label);
        }
    });
});
