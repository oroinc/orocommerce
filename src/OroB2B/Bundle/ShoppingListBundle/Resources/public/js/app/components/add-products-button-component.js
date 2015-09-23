define(function(require) {
    'use strict';

    var AddProductsButtonComponent;
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var AddButtonAbstractComponent = require('orob2bshoppinglist/js/app/components/add-button-abstract-component');

    AddProductsButtonComponent = AddButtonAbstractComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, {
                mediatorPrefix: 'frontend:shoppinglist',
                gridWidget: 'shopping_list_add_product_grid'
            });

            mediator.on('widget_success:' + this.options.gridWidget, this.onGridWidgetSuccess, this);

            AddProductsButtonComponent.__super__.initialize.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        onGridWidgetSuccess: function(options) {
            if (options.hasOwnProperty('shoppingListId')) {
                this.reloadWidget(options.shoppingListId);
            }
        },

        /**
         * @inheritDoc
         */
        selectShoppingList: function(shoppingListId) {
            AddProductsButtonComponent.__super__.selectShoppingList.apply(this, arguments);
            mediator.trigger(this.options.mediatorPrefix + ':products-add', {shoppingListId: shoppingListId});
        },

        /**
         * @param {Object} selections
         */
        showForm: function(selections) {
            if (!selections.cnt) {
                messenger.notificationFlashMessage('warning', selections.reason);
                return;
            }
            AddProductsButtonComponent.__super__.showForm.call(this);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('widget_success:' + this.options.gridWidget);

            AddProductsButtonComponent.__super__.dispose.call(this);
        }
    });

    return AddProductsButtonComponent;
});
