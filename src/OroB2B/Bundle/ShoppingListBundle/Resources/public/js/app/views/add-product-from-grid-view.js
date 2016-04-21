define(function(require) {
    'use strict';

    var AddProductFromGridView;
    var AddProductView = require('orob2bshoppinglist/js/app/views/add-product-view');
    var $ = require('jquery');

    AddProductFromGridView = AddProductView.extend({
        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            var el = $(e.target);
            var urlOptions = el.data('urloptions');

            urlOptions.productId = el.closest('.pinned-dropdown').data('product').id;
            el.data('urloptions', urlOptions);
            AddProductFromGridView.__super__.onClick.call(this, e);
        }
    });

    return AddProductFromGridView;
});
