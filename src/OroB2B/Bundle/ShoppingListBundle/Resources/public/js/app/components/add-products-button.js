/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';

    var $ = require('jquery'),
        mediator = require('oroui/js/mediator'),
        options = {
            successMessage: 'orob2b.shoppinglist.menu.add_products.success.message',
            errorMessage: 'orob2b.shoppinglist.menu.add_products.error.message',
            redirect: '/'
        };

    function onClick(e) {
        e.preventDefault();
        mediator.trigger('frontend:shoppinglist:products-add', {id: $(this).data('id')})
    }

    return function (additionalOptions) {
        _.extend(options, additionalOptions || {});
        var button;
        button = options._sourceElement;
        button.click($.proxy(onClick, null));
    };
});
