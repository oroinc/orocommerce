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
            shoppingListVisible: 6,
            selectedShoppingList: null
        },

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.eventChannelId = options.eventChannelId;
            this.selectedShoppingList = options.selectedShoppingList;

            // listen to incoming title updates and re-render the whole shopping list
            mediator.on('shopping-list-event:' + this.eventChannelId + ':update', this.updateCurrentTitle, this);

            mediator.on('shopping-list-event:' + this.eventChannelId + ':update', this.updateAdditionalDropdown, this);

            this.showAdditionalDropdown(options.shoppingListCount);
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
         * @param shoppingListCount
         */
        showAdditionalDropdown: function(shoppingListCount) {
            var shoppingListVisible = this.options.shoppingListVisible;

            if (shoppingListCount >= shoppingListVisible) {
                this.$el.addClass('shopping-list--fulfilled');

                this.$el.parent().next().next().addClass('shopping-list-more--visible');
            }
        },

        /**
         *
         * @param updateData
         */
        updateAdditionalDropdown: function(updateData) {
            this.$el.parent().next().next().find('.shopping-list-links__item:nth-child(' + this.selectedShoppingList + ') .shopping-list-links__text').text(updateData.label);
        }
    });

    return ShoppingListSidebarView;
});
