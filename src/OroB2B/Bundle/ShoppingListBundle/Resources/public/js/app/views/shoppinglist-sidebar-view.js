/*jslint nomen:true*/
/*global define*/
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
            mediator.on('shopping-list-event:' + this.options.eventChannelId + ':update', this.updateCurrentTitle, this);

            this.hideTail(this.options.shoppingListVisible);

            this.showAdditionalDropdown(this.options.shoppingListCount);
        },

        /**
         *
         * @param updateData
         */
        updateCurrentTitle: function(updateData) {
            this.options._sourceElement.find('.current-title').text(updateData.label);
        },

        /**
         *
         * @param shoppingListVisible
         */
        hideTail: function(shoppingListVisible) {
            if (shoppingListVisible > 0) {
                this.options._sourceElement
                    .find('.shopping-list__item:nth-child(n+' + shoppingListVisible + ')')
                    .addClass('shopping-list__item--hidden');
            }
        },

        /**
         *
         * @param shoppingListCount
         */
        showAdditionalDropdown: function(shoppingListCount) {
            var shoppingListVisible = this.options.shoppingListVisible;

            if (shoppingListCount >= shoppingListVisible) {
                this.options._sourceElement
                    .find('.shopping-list')
                    .addClass('shopping-list--fulfilled');

                this.options._sourceElement
                    .next('.shopping-list-dropdown')
                    .addClass('shopping-list-dropdown--visible');
            }
        }
    });

    return ShoppingListSidebarView;
});
