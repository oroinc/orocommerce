define(function(require) {
    'use strict';

    var AddProductFromGridView;
    var AddProductView = require('orob2bshoppinglist/js/app/views/add-product-view');
    var $ = require('jquery');

    AddProductFromGridView = AddProductView.extend({
        initialize: function(options) {
            AddProductFromGridView.__super__.initialize.apply(this, arguments);

            var product = this.$el.data('product');
            this.$el.find('.pinned-dropdown').data('product', product);
            this.initLayout();
        },

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
