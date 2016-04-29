define(function(require) {
    'use strict';

    var ProductAddToShoppingListHandler;
    var ShoppingListWidget = require('orob2bshoppinglist/js/app/widget/shopping-list-widget');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var Error = require('oroui/js/error');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductAddToShoppingListHandler = {
        onClick: function(view, $button) {
            var url = $button.data('url');
            var formData = $button.closest('form').serialize();
            var urlOptions = {
                productId: view.model.get('id')
            };

            if ($button.data('intention') === 'new') {
                this._createNewShoppingList(url, urlOptions, formData);
            } else {
                var shoppingList = $button.data('shoppinglist');
                urlOptions.shoppingListId = shoppingList.id;
                this._addProductToShoppingList(url, urlOptions, formData);
            }
        },

        /**
         * @param {String} url
         * @param {Object} urlOptions
         * @param {Object} formData
         */
        _createNewShoppingList: function(url, urlOptions, formData) {
            var dialog = new ShoppingListWidget({});
            dialog.on('formSave', _.bind(function(response) {
                urlOptions.shoppingListId = response;
                this._addProductToShoppingList(url, urlOptions, formData);
            }, this));
            dialog.render();
        },

        /**
         * @param {String} url
         * @param {Object} urlOptions
         * @param {Object} formData
         */
        _addProductToShoppingList: function(url, urlOptions, formData) {
            mediator.execute('showLoading');
            $.ajax({
                type: 'POST',
                url: routing.generate(url, urlOptions),
                data: formData,
                success: function(response) {
                    mediator.execute('hideLoading');
                    if (response && response.message) {
                        mediator.execute(
                            'showFlashMessage', (response.hasOwnProperty('successful') ? 'success' : 'error'),
                            response.message
                        );
                    }
                    var event = urlOptions.shoppingListId ? 'shopping-list:updated' : 'shopping-list:created';
                    mediator.trigger(event, response.shoppingList, response.product);
                },
                error: function(xhr) {
                    mediator.execute('hideLoading');
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        }
    };

    return ProductAddToShoppingListHandler;
});
