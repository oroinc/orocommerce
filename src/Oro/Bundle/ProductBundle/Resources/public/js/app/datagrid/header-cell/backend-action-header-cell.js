define(function(require) {
    'use strict';

    var BackendSelectHeaderCell;
    var template = require('tpl!oroproduct/templates/datagrid/backend-action-header-cell.html');
    var SelectHeaderCell = require('orodatagrid/js/datagrid/header-cell/action-header-cell');

    BackendSelectHeaderCell = SelectHeaderCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
        className: 'product-action',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template
    });

    return BackendSelectHeaderCell;
});
