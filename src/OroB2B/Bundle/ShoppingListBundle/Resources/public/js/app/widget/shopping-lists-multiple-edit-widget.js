define(function(require) {
    'use strict';

    var ShoppingListsMultipleEditWidget;
    var ContentWidget = require('orob2bshoppinglist/js/app/widget/content-widget');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    ShoppingListsMultipleEditWidget = ContentWidget.extend({
        events: {
            'click [data-name="shopping-list-edit"]': 'edit',
            'click [data-name="shopping-list-delete"]': 'delete',
            'click [data-name="shopping-list-accept"]': 'accept',
            'click [data-name="shopping-list-decline"]': 'decline',
            'click [data-name="shopping-lists-close"]': 'close'
        },

        template: '',

        initialize: function(options) {
            if (!this.model) {
                return;
            }
            this.template = options.template;

            options.title = this.model.get('name');
            options.content = this.getPopupContent();
            options.dialogOptions = {
                'modal': true,
                'resizable': false,
                'width': 580,
                'autoResize': true
            };

            ShoppingListsMultipleEditWidget.__super__.initialize.apply(this, arguments);
        },

        dispose: function() {
            ShoppingListsMultipleEditWidget.__super__.dispose.apply(this, arguments);
        },

        edit: function() {
            console.log('edit');
        },

        delete: function() {
            console.log('delete');
        },

        accept: function() {
            console.log('accept');
        },

        decline: function() {
            console.log('decline');
        },
        
        close: function() {
            this.remove();
        },

        getPopupContent: function() {
            var popupData = {};

            popupData.shoppingLists = this.model.get('shopping_lists');
            return this.template({popupData: popupData});
        }
    });

    return ShoppingListsMultipleEditWidget;
});
