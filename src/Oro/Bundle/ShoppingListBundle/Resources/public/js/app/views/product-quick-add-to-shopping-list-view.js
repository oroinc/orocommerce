define(function(require) {
    'use strict';

    var ProductQuickAddToShoppingListView;
    var ProductAddToShoppingListView = require('oroshoppinglist/js/app/views/product-add-to-shopping-list-view');
    var mediator = require('oroui/js/mediator');

    ProductQuickAddToShoppingListView = ProductAddToShoppingListView.extend({
        /**
         * @inheritDoc
         */
        constructor: function ProductQuickAddToShoppingListView() {
            ProductQuickAddToShoppingListView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ProductQuickAddToShoppingListView.__super__.initialize.apply(this, arguments);
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
            e.stopPropagation();

            if (this.formHasErrors()) {
                return;
            }

            ProductQuickAddToShoppingListView.__super__.onClick.apply(this, arguments);
        },

        formHasErrors: function() {
            return !this.$form.validate().valid() ||
                this.$form.find('.product-autocomplete-error .validation-failed:visible').length;
        }
    });

    return ProductQuickAddToShoppingListView;
});
