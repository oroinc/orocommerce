define(function(require) {
    'use strict';

    var ForntendProductSidebarComponent;
    var ProductSidebarComponent = require('orob2bcatalog/js/app/components/product-sidebar-component');

    /**
     * Options:
     * - defaultCategoryId - default selected category id
     *
     * @export orob2bcatalog/js/app/components/tree-manage-component
     * @extends orob2bcatalog.app.components.ProductSidebarComponent
     * @class orob2bcatalog.app.components.ForntendProductSidebarComponent
     */
    ForntendProductSidebarComponent = ProductSidebarComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            sidebarAlias: 'frontend-products-sidebar',
            includeSubcategoriesSelector: '.include-sub-categories-choice input[type=checkbox]'
        }
    });

    return ForntendProductSidebarComponent;
});
