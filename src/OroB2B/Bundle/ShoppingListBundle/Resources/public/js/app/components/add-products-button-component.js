/*jslint nomen: true*/
/*global define*/
define(function(require) {
    'use strict';

    var AddProductsButtonComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var DialogWidget = require('oro/dialog-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var options = {
        successMessage: 'orob2b.shoppinglist.menu.add_products.success.message',
        errorMessage: 'orob2b.shoppinglist.menu.add_products.error.message',
        redirect: '/',
        intention: {
            create_new: 'new'
        }
    };
    AddProductsButtonComponent = BaseComponent.extend({
        initialize: function(additionalOptions) {
            _.extend(options, additionalOptions || {});
            mediator.on('frontend:shoppinglist:add-widget-requested-response', this.showForm, this);
            options._sourceElement.find('.grid-control').click($.proxy(this.onClick, null));
        },
        onClick: function(e) {
            e.preventDefault();

            if ($(e.currentTarget).data('intention') === options.intention.create_new) {
                mediator.trigger('frontend:shoppinglist:add-widget-requested');
            } else {
                mediator.trigger('frontend:shoppinglist:products-add', {shoppingListId: $(this).data('id')});
            }
        },
        showForm: function(selections) {
            if (!selections.cnt) {
                messenger.notificationFlashMessage('warning', selections.reason);
                return;
            }
            var dialog = new DialogWidget({
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
            dialog.render();
            dialog.on('formSave', _.bind(function(response) {
                mediator.trigger('frontend:shoppinglist:products-add', {shoppingListId: response});
                $('.btn[data-intention="current"]').data('id', response);
            }, this));
        },
        dispose: function() {
            if (this.disposed) {
                return;
            }

            options._sourceElement.find('.grid-control').off();
            mediator.off('frontend:shoppinglist:add-widget-requested-response', this.showForm, this);
            AddProductsButtonComponent.__super__.dispose.call(this);
        }
    });

    return AddProductsButtonComponent;
});

