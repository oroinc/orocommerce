define(function(require) {
    'use strict';

    var BackendSelectHeaderCell;
    var _  = require('underscore');
    var $ = require('jquery');
    //var template = require('tpl!orodatagrid/templates/datagrid/action-header-cell.html');
    var SelectHeaderCell = require('orodatagrid/js/datagrid/header-cell/action-header-cell');

    BackendSelectHeaderCell = SelectHeaderCell.extend({
        /** @property */
        className: 'select-all-header-cell SelectHeaderCell',

        /** @property */
        tagName: 'div',

        /** @property */
        //template: template,

        /** @property */
        container: '.oro-datagrid',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            BackendSelectHeaderCell.__super__.initialize.apply(this, arguments);
            this.render();
        },

        /**
         * @inheritDoc
         */
        render: function() {
            BackendSelectHeaderCell.__super__.render.call(this);
            $(this.container).before(this.$el);

            return this;
        }
    });

    return BackendSelectHeaderCell;
});
