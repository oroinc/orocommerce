define(function(require) {
    'use strict';

    var FrontendProductSidebarComponent;
    var _ = require('underscore');
    var ProductSidebarComponent = require('orocatalog/js/app/components/product-sidebar-component');

    /**
     * @export orocatalog/js/app/components/product-sidebar-component
     * @extends orocatalog.app.components.ProductSidebarComponent
     * @class orocatalog.app.components.FrontendProductSidebarComponent
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
