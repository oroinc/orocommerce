define(function(require) {
    'use strict';

    var ShoppingListWidget;
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');

    ShoppingListWidget = DialogWidget.extend({
        initialize: function(options) {
            var self = this;

            this.options.title = __('orob2b.shoppinglist.widget.add_to_new_shopping_list');
            this.options.url = routing.generate('orob2b_shopping_list_frontend_create');
            this.options.regionEnabled = false;
            this.options.incrementalPosition = false;

            options.dialogOptions = {
                'modal': true,
                'resizable': false,
                'width': '480',
                'autoResize': true,
                'dialogClass': 'shopping-list-dialog'
            };

            this.on('formSave', function(id) {
                var label = self.$el.find('.form-field-label').val();
                mediator.trigger('shopping-list:created', {
                    id: id,
                    label: label
                });
            });

            ShoppingListWidget.__super__.initialize.apply(this, arguments);
        }
    });

    return ShoppingListWidget;
});
