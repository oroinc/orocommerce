/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ShoppingListWidgetComponent;
    var dialog;
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ShoppingListWidgetComponent = BaseComponent.extend({
        createDialog: function() {
            dialog = new DialogWidget({
                'url': routing.generate('orob2b_shopping_list_frontend_create'),
                'title': 'Create new Shopping List',
                'regionEnabled': false,
                'incrementalPosition': false,
                'dialogOptions': {
                    'modal': true,
                    'resizable': false,
                    'width': '460',
                    'autoResize': true
                }
            });

            return dialog;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            ShoppingListWidgetComponent.__super__.dispose.call(this);
        }
    });

    return ShoppingListWidgetComponent;
});
