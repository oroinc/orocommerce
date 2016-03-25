define(function(require) {
    'use strict';

    var FrontendProductSidebarComponent;
    var _ = require('underscore');
    var ProductSidebarComponent = require('orob2bcatalog/js/app/components/product-sidebar-component');

    /**
     * @export orob2bcatalog/js/app/components/product-sidebar-component
     * @extends orob2bcatalog.app.components.ProductSidebarComponent
     * @class orob2bcatalog.app.components.FrontendProductSidebarComponent
     */
    FrontendProductSidebarComponent = ProductSidebarComponent.extend({
        /**
         * @param {Object} options
         */
        initialize: function(options) {
            FrontendProductSidebarComponent.__super__.initialize.call(this, options);
            _.extend(this.options, {sidebarAlias: 'frontend-products-sidebar'});
        }
    });

    return FrontendProductSidebarComponent;
});
