define(function (require) {
    'use strict';

    var FrontendTreeViewComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var TreeViewComponent = require('orob2bcatalog/js/app/components/tree-view-component');

    /**
     * Options:
     * - defaultCategoryId - default selected category id
     *
     * @export orob2bcatalog/js/app/components/tree-manage-component
     * @extends orob2bcatalog.app.components.TreeViewComponent
     * @class orob2bcatalog.app.components.FrontendTreeViewComponent
     */
    FrontendTreeViewComponent = TreeViewComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            sidebarAlias: 'frontend-products-sidebar'
        },
    });

    return FrontendTreeViewComponent;
});
