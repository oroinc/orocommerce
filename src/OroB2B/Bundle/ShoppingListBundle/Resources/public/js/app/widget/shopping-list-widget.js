define(function(require) {
    'use strict';

    var ShoppingListWidget;
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');
    var __ = require('orotranslation/js/translator');

    var config = require('module').config();

    ShoppingListWidget = DialogWidget.extend({
        initialize: function(options) {
            this.options.title = __('orob2b.shoppinglist.widget.add_to_new_shopping_list');
            // TODO: remove config.routeParams after implement Layout for all pages used this widget
            this.options.url = routing.generate('orob2b_shopping_list_frontend_create', config.routeParams || {});
            this.options.regionEnabled = false;
            this.options.incrementalPosition = false;

            options.dialogOptions = {
                'modal': true,
                'resizable': false,
                'width': '480',
                'autoResize': true,
                'dialogClass': 'shopping-list-dialog'
            };

            ShoppingListWidget.__super__.initialize.apply(this, arguments);
        }
    });

    return ShoppingListWidget;
});
