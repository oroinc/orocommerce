define(function(require) {
    'use strict';

    var ForntendProductSidebarComponent;
    var _ = require('underscore');
    var ProductSidebarComponent = require('orob2bcatalog/js/app/components/product-sidebar-component');

    /**
     * Options:
     * - defaultCategoryId - default selected category id
     *
     * @export orob2bcatalog/js/app/components/product-sidebar-component
     * @extends orob2bcatalog.app.components.ProductSidebarComponent
     * @class orob2bcatalog.app.components.ForntendProductSidebarComponent
     */
    ForntendProductSidebarComponent = ProductSidebarComponent.extend({
        /**
         * @param {Object} options
         */
        initialize: function(options) {
            ForntendProductSidebarComponent.__super__.initialize.call(this, options);
            _.extend(this.options, {sidebarAlias: 'frontend-products-sidebar'});
        }
    });

    return ForntendProductSidebarComponent;
});
