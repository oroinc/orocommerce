/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';

    var $ = require('jquery'),
        _ = require('underscore'),
        mediator = require('oroui/js/mediator'),
        DialogWidget = require('oro/dialog-widget'),
        routing = require('routing'),
        messenger = require('oroui/js/messenger'),
        options = {
            successMessage: 'orob2b.shoppinglist.menu.add_products.success.message',
            errorMessage: 'orob2b.shoppinglist.menu.add_products.error.message',
            redirect: '/',
            intention: {
                create_new: 'new'
            }
        };

    mediator.on('frontend:shoppinglist:add-widget-requested-response', showForm, this);

    function onClick(e) {
        e.preventDefault();

        if ($(e.currentTarget).data('id') === options.intention.create_new) {
            mediator.trigger('frontend:shoppinglist:add-widget-requested');
        } else {
            mediator.trigger('frontend:shoppinglist:products-add', {id: $(this).data('id')});
        }
    }

    function showForm(selections) {
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
                'width': '675',
                'autoResize': true
            }
        });
        dialog.render();
        dialog.on('formSave', _.bind(function (response) {
            mediator.trigger('frontend:shoppinglist:products-add', {id: response});
        }, this));
    }

    return function (additionalOptions) {
        _.extend(options, additionalOptions || {});
        var button;
        button = options._sourceElement;
        button.click($.proxy(onClick, null));
    };
});
