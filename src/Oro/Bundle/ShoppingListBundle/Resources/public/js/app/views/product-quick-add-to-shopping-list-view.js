define(function(require) {
    'use strict';

    var ProductQuickAddToShoppingListView;
    var ProductAddToShoppingListView = require('oroshoppinglist/js/app/views/product-add-to-shopping-list-view');
    var mediator = require('oroui/js/mediator');

    ProductQuickAddToShoppingListView = ProductAddToShoppingListView.extend({
        initialize: function(options) {
            ProductQuickAddToShoppingListView.__super__.initialize.apply(this, arguments);
            this.options.quickAddComponentPrefix = options.quickAddComponentPrefix;

            if (this.formHasErrors()) {
                this.$el.addClass('btn-inactive');
            }

            this.$el.find('.add-to-shopping-list-button').on('click', _.bind(this.submit, this));
            this.$el.on('click', _.bind(this.submit, this));
        },

        _addProductToShoppingList: function(url, urlOptions, formData) {
            mediator.trigger(
                this.options.quickAddComponentPrefix + ':submit',
                'oro_shopping_list_quick_add_processor',
                urlOptions.shoppingListId
            );
        },

        /**
         * @param {$.Event} e
         */
        submit: function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (this.formHasErrors()) {
                return;
            }
        },

        formHasErrors: function() {
            return this.$el.closest('.validation-info').find('.import-errors').length;
        }
    });

    return ProductQuickAddToShoppingListView;
});
