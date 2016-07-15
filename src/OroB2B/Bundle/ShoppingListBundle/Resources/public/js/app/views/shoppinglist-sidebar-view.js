/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    var ShoppingListSidebarView;

    ShoppingListSidebarView = BaseView.extend({

        eventChannelId: null,

        /**
         * @property {Object}
         */
        options: {
            shoppingListCount: null,
            shoppingListVisible: 6
        },

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = options._sourceElement;
            this.eventChannelId = options.eventChannelId;
            this.selectedShoppingList = options.selectedShoppingList;

            // listen to incoming title updates and re-render the whole shopping list
            mediator.on('shopping-list-event:' + this.eventChannelId + ':update', this.updateCurrentTitle, this);

            this.hideTail(this.options.shoppingListVisible);

            this.showAdditionalDropdown(this.options.shoppingListCount);
        },

        /**
         *
         * @param updateData
         */
        updateCurrentTitle: function(updateData) {
            this.$el.find('.current-title').text(updateData.label);
        },

        /**
         *
         * @param shoppingListVisible
         */
        hideTail: function(shoppingListVisible) {
            if (shoppingListVisible > 0) {
                this.$el.find('.shopping-list__item:nth-child(n+' + shoppingListVisible + ')').addClass('shopping-list__item--hidden');
            }
        },

        /**
         *
         * @param shoppingListCount
         */
        showAdditionalDropdown: function(shoppingListCount) {
            var shoppingListVisible = this.options.shoppingListVisible;

            if (shoppingListCount >= shoppingListVisible) {
                this.$el.find('.shopping-list').addClass('shopping-list--fulfilled');

                this.$el.next('.shopping-list-dropdown').addClass('shopping-list-dropdown--visible');
            }
        }
    });

    return ShoppingListSidebarView;
});
