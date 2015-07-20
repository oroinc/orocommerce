/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';

    var $ = require('jquery'),
        mediator = require('oroui/js/mediator'),
        DialogWidget = require('oro/dialog-widget'),
        routing = require('routing'),
        options = {
            successMessage: 'orob2b.shoppinglist.menu.add_products.success.message',
            errorMessage: 'orob2b.shoppinglist.menu.add_products.error.message',
            redirect: '/'
        };

    function onClick(e) {
        e.preventDefault();

        if ($(e.currentTarget).data('id') === 'new') {
            showForm(); return;
        }

        mediator.trigger('frontend:shoppinglist:products-add', {id: $(this).data('id')})
    }

    function showForm() {
        var dialog = new DialogWidget({
            'url': routing.generate('orob2b_shopping_list_frontend_create'),
            'title': 'orob2b.shoppinglist.actions.add_to_new_shopping_list',
            'regionEnabled': false,
            'incrementalPosition': false,
            'dialogOptions': {
                'modal': true,
                'resizable': false,
                'width': 675,
                'autoResize': true
            }
        });
        dialog.render();
        dialog.on('formSave', _.bind(function (response) {
            dialog.remove();
            mediator.trigger('frontend:shoppinglist:products-add', {id: response });
        }, this));
    }

    return function (additionalOptions) {
        _.extend(options, additionalOptions || {});
        var button;
        button = options._sourceElement;
        button.click($.proxy(onClick, null));
    };
});
