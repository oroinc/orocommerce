define(function(require) {
    'use strict';

    var WebCatalogTreeComponent;
    var BasicTreeManageComponent = require('oroui/js/app/components/basic-tree-manage-component');

    WebCatalogTreeComponent = BasicTreeManageComponent.extend({
        /**
         * @property {Boolean}
         */
        checkboxEnabled: true
    });

    return WebCatalogTreeComponent;
});
