define(function(require) {
    'use strict';

    var ShoppingListCreateWidget;
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');
    var _ = require('underscore');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    ShoppingListCreateWidget = DialogWidget.extend({
        shoppingListCollection: null,

        initialize: function(options) {
            var urlOptions = {};
            if (options.createOnly) {
                urlOptions.createOnly = true;
            }
            this.options.url = options.url = routing.generate('oro_shopping_list_frontend_create', urlOptions);

            this.options.title = _.__('oro.shoppinglist.widget.add_to_new_shopping_list');
            this.options.regionEnabled = false;
            this.options.incrementalPosition = false;

            options.dialogOptions = {
                'modal': true,
                'resizable': false,
                'width': '480',
                'autoResize': true,
                'dialogClass': 'shopping-list-dialog'
            };

            this.on('formSave', _.bind(this.onFormSave, this));

            ShoppingListCollectionService.shoppingListCollection.done(_.bind(function(collection) {
                this.shoppingListCollection = collection;
            }, this));

            ShoppingListCreateWidget.__super__.initialize.apply(this, arguments);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.shoppingListCollection;
            return ShoppingListCreateWidget.__super__.dispose.apply(this, arguments);
        },

        onFormSave: function(id) {
            var label = this.$el.find('.form-field-label').val();
            if (this.shoppingListCollection.length) {
                this.shoppingListCollection.each(function(model) {
                    model.set('is_current', model.get('id') === id, {silent: true});
                });
            }

            this.shoppingListCollection.add({
                id: id,
                label: label,
                is_current: true
            });
            this.shoppingListCollection.trigger('change');
        }
    });

    return ShoppingListCreateWidget;
});
