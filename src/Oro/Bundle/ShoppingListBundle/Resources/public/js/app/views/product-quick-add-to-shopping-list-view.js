define(function(require) {
    'use strict';

    const ProductAddToShoppingListView = require('oroshoppinglist/js/app/views/product-add-to-shopping-list-view');
    const mediator = require('oroui/js/mediator');

    const ProductQuickAddToShoppingListView = ProductAddToShoppingListView.extend({
        /**
         * @inheritdoc
         */
        constructor: function ProductQuickAddToShoppingListView(options) {
            ProductQuickAddToShoppingListView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ProductQuickAddToShoppingListView.__super__.initialize.call(this, options);
            this.options.quickAddComponentPrefix = options.quickAddComponentPrefix;
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
        onClick: function(e) {
            e.preventDefault();

            if (this.formHasErrors()) {
                return;
            }

            ProductQuickAddToShoppingListView.__super__.onClick.call(this, e);
        },

        formHasErrors: function() {
            return !this.$form.validate().valid() ||
                this.$form.find('.product-autocomplete-error .validation-failed:visible').length;
        }
    });

    return ProductQuickAddToShoppingListView;
});
