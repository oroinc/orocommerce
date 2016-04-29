define(function(require) {
    'use strict';

    var ProductAddToRfqHandler;
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');

    ProductAddToRfqHandler = {
        onClick: function(view, $button) {
            var productOptions = {
                product_id: view.model.get('id'),
                quantity: view.model.get('quantity'),
                unit: view.model.get('unit')
            };
            var url = routing.generate($button.data('url'), {
                product_items: [productOptions]
            });
            mediator.execute('showLoading');
            mediator.execute('redirectTo', {url: url}, {redirect: true});
        }
    };

    return ProductAddToRfqHandler;
});
