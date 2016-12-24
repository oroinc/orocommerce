define(function(require) {
    'use strict';

    var ShoppingListWidgetView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    ShoppingListWidgetView = BaseView.extend({
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

        initialize: function(options) {
            ShoppingListWidgetView.__super__.initialize.apply(this, arguments);

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
            return ShoppingListWidgetView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.updateLabel();
            this.updateRadio();
        },

        updateLabel: function() {
            var self = this;
            self.$el.find(this.elements.shoppingListTitle).each(function() {
                var $title = $(this);
                var shoppingListId = $title.data('shopping-list-id');
                $title.html(self.shoppingListCollection.get(shoppingListId).get('label'));
            });
        },

        updateRadio: function() {
            var self = this;
            self.$el.find(this.elements.shoppingListCurrentLabel).each(function() {
                var $label = $(this);
                var $input = $label.find('input');
                var shoppingListId = $label.data('shopping-list-id');
                var isCurrent = self.shoppingListCollection.get(shoppingListId).get('is_current');

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
