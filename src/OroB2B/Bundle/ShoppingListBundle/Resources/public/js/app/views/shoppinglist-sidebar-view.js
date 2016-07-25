define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    var ShoppingListSidebarView;

    ShoppingListSidebarView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            eventChannelId: null,
            shoppingListCount: null,
            shoppingListVisible: 6
        },

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            // listen to incoming title updates and re-render the whole shopping list
            mediator.on('shopping-list-event:' + this.options.eventChannelId + ':update',
                        this.updateCurrentTitle, this);

            this.showAdditionalDropdown(this.options.shoppingListCount);
        },

        /**
         *
         * @param updateData
         */
        updateCurrentTitle: function(updateData) {
            this.options._sourceElement.find('[data-current-title]').text(updateData.label);
        },

        /**
         *
         * @param shoppingListCount
         */
        showAdditionalDropdown: function(shoppingListCount) {
            var shoppingListVisible = this.options.shoppingListVisible;

            if (shoppingListCount > shoppingListVisible) {
                this.options._sourceElement
                    .next('.shopping-list-dropdown')
                    .addClass('shopping-list-dropdown--visible');
            }
        }
    });

    return ShoppingListSidebarView;
});
