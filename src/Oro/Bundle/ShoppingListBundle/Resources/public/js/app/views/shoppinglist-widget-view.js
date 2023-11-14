define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const $ = require('jquery');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    const ShoppingListWidgetView = BaseView.extend({
        options: {
            currentClass: ''
        },

        /**
         * Backbone.Collection {Object}
         */
        shoppingListCollection: null,

        /**
         * @inheritdoc
         */
        constructor: function ShoppingListWidgetView(options) {
            ShoppingListWidgetView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ShoppingListWidgetView.__super__.initialize.call(this, options);

            this.options = _.defaults(options || {}, this.options);
            this.$el = $(this.options._sourceElement);

            ShoppingListCollectionService.shoppingListCollection.done(collection => {
                this.shoppingListCollection = collection;
                this.listenTo(collection, 'change', this.render);
                this.render();
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.shoppingListCollection;
            return ShoppingListWidgetView.__super__.dispose.call(this);
        },

        render: function() {
            const $shoppingListWidget = this.$el.closest('.shopping-list-widget');
            const showShoppingListDropdown =
                this.shoppingListCollection.length ||
                $shoppingListWidget.find('[data-shopping-list-create]').length;

            $shoppingListWidget.toggleClass(
                'shopping-list-widget--disabled',
                !showShoppingListDropdown
            );

            $shoppingListWidget.find('.shopping-list-trigger')
                .toggleClass('disabled', !showShoppingListDropdown)
                .attr('disabled', !showShoppingListDropdown);

            this.$('[data-role="set-default"]:checked').closest('.shopping-list-dropdown__item')
                .addClass('shopping-list-dropdown__item--default')
                .siblings()
                .removeClass('shopping-list-dropdown__item--default');
        }
    });

    return ShoppingListWidgetView;
});
