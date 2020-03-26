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

        elements: {
            shoppingListTitle: '[data-role="shopping-list-title"]',
            shoppingListCurrentLabel: '[data-role="shopping-list-current-label"]'
        },

        /**
         * Backbone.Collection {Object}
         */
        shoppingListCollection: null,

        /**
         * @inheritDoc
         */
        constructor: function ShoppingListWidgetView(options) {
            ShoppingListWidgetView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ShoppingListWidgetView.__super__.initialize.call(this, options);

            this.options = _.defaults(options || {}, this.options);
            this.$el = $(this.options._sourceElement);

            ShoppingListCollectionService.shoppingListCollection.done(_.bind(function(collection) {
                this.shoppingListCollection = collection;
                this.listenTo(collection, 'change', this.render);
                this.render();
            }, this));
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
                $shoppingListWidget.find('.shopping-list-widget__create-btn').length;

            $shoppingListWidget.toggleClass(
                'shopping-list-widget--disabled',
                !showShoppingListDropdown
            );

            $shoppingListWidget.find('.header-row__trigger')
                .toggleClass('disabled', !showShoppingListDropdown)
                .attr('disabled', !showShoppingListDropdown);

            this.updateLabel();
            this.updateRadio();
        },

        updateLabel: function() {
            const self = this;
            self.$el.find(this.elements.shoppingListTitle).each(function() {
                const $title = $(this);
                const shoppingListId = $title.data('shopping-list-id');
                $title.html(_.escape(self.shoppingListCollection.get(shoppingListId).get('label')));
            });
        },

        updateRadio: function() {
            const self = this;
            self.$el.find(this.elements.shoppingListCurrentLabel).each(function() {
                const $label = $(this);
                const $input = $label.find('input');
                const shoppingListId = $label.data('shopping-list-id');
                const isCurrent = self.shoppingListCollection.get(shoppingListId).get('is_current');

                $label.removeClass('checked');
                if (isCurrent) {
                    $label.addClass('checked');
                }
                $input.prop('checked', isCurrent);
                $label.removeClass(self.options.currentClass);
                if (isCurrent) {
                    $label.addClass(self.options.currentClass);
                }
                if ($input.length) {
                    $input.prop('checked', isCurrent);
                }
            });
        }
    });

    return ShoppingListWidgetView;
});
