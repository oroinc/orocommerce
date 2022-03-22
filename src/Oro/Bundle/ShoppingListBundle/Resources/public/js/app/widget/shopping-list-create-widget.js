define(function(require) {
    'use strict';

    const routing = require('routing');
    const DialogWidget = require('oro/dialog-widget');
    const _ = require('underscore');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    const ShoppingListCreateWidget = DialogWidget.extend({
        shoppingListCollection: null,

        /**
         * @inheritdoc
         */
        constructor: function ShoppingListCreateWidget(options) {
            ShoppingListCreateWidget.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const urlOptions = {};
            if (options.createOnly) {
                urlOptions.createOnly = true;
            }
            this.options.url = options.url = routing.generate('oro_shopping_list_frontend_create', urlOptions);

            this.options.title = _.__('oro.shoppinglist.widget.add_to_new_shopping_list');
            this.options.regionEnabled = false;
            this.options.incrementalPosition = false;
            this.options.shoppingListCreateEnabled = true;

            options.dialogOptions = {
                modal: true,
                resizable: false,
                width: 604,
                minWidth: 375,
                autoResize: true,
                dialogClass: 'shopping-list-dialog'
            };

            this.on('formSave', this.onFormSave.bind(this));

            ShoppingListCollectionService.shoppingListCollection.done(collection => {
                this.shoppingListCollection = collection;
            });

            ShoppingListCreateWidget.__super__.initialize.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.shoppingListCollection;
            return ShoppingListCreateWidget.__super__.dispose.call(this);
        },

        onFormSave: function(data) {
            const label = data.label || this.$el.find('.form-field-label').val();
            if (this.shoppingListCollection.length) {
                this.shoppingListCollection.each(function(model) {
                    model.set('is_current', model.get('id') === data.savedId, {silent: true});
                });
            }

            this.shoppingListCollection.add({
                id: data.savedId,
                label: label,
                is_current: true
            });
            this.shoppingListCollection.trigger('change', {
                shoppingListCreateEnabled: data.shoppingListCreateEnabled
            });
        }
    });

    return ShoppingListCreateWidget;
});
