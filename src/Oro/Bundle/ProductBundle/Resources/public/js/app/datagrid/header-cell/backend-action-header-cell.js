define(function(require) {
    'use strict';

    var BackendSelectHeaderCell;
    var _ = require('underscore');
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
        template: template,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            BackendSelectHeaderCell.__super__.initialize.apply(this, arguments);
            this.selectState = options.selectState;
            this.listenTo(this.selectState, 'change', _.bind(_.debounce(this.canUse, 50), this));
        },

        canUse: function(selectState) {
            this[(selectState.isEmpty() && selectState.get('inset')) ? 'disable' : 'enable' ]();
        }
    });

    return BackendSelectHeaderCell;
});
