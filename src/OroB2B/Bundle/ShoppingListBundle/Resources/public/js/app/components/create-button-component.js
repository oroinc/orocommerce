/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var CreateButtonComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ShoppingListWidget = require('orob2bshoppinglist/js/app/widget/shopping-list-widget');
    var widgetManager = require('oroui/js/widget-manager');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var _ = require('underscore');

    CreateButtonComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            widgetAlias: 'shopping_lists_frontend_widget'
        },

        /**
         * @property {jQuery.Element}
         */
        dialog: null,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            _.extend(this.options, options || {});

            this.options._sourceElement.on('click', 'a', _.bind(this.onClick, this));

            mediator
                .on('shopping-list:created', this.renderWidget, this)
                .on('shopping-list:updated', this.renderWidget, this)
                .on('frontend:item:delete', this.renderWidget, this);
        },

        renderWidget: function() {
            widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
                widget.render();
            });
        },

        onClick: function() {
            this.dialog = new ShoppingListWidget({});
            this.dialog.setUrl(routing.generate('orob2b_shopping_list_frontend_create', {createOnly: true}));

            this.dialog.render();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator
                .off('shopping-list:created', this.renderWidget, this)
                .off('shopping-list:updated', this.renderWidget, this)
                .off('frontend:item:delete', this.renderWidget, this);

            this.options._sourceElement.off();

            CreateButtonComponent.__super__.dispose.call(this);
        }
    });

    return CreateButtonComponent;
});
