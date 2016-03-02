/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AddProductFromIndexComponent;
    var AddProductFromViewComponent = require('orob2bshoppinglist/js/app/components/add-product-from-view-component');
    var $ = require('jquery');
    var _ = require('underscore');

    AddProductFromIndexComponent = AddProductFromViewComponent.extend({
        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            var el = $(e.target);
            var urlOptions = el.data('urloptions');

            urlOptions.productId = el.closest('.add-product-from-view-component').data('product-id');
            el.data('urloptions', urlOptions);
            AddProductFromIndexComponent.__super__.onClick.call(this, e);
        }
    });

    return AddProductFromIndexComponent;
});
