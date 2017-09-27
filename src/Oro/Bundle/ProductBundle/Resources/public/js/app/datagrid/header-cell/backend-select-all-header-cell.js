define(function(require) {
    'use strict';

    var BackendSelectAllHeaderCell;
    var _  = require('underscore');
    var $ = require('jquery');
    var template = require('tpl!orodatagrid/templates/datagrid/select-all-header-cell.html');
    var SelectAllHeaderCell = require('orodatagrid/js/datagrid/header-cell/select-all-header-cell');

    BackendSelectAllHeaderCell = SelectAllHeaderCell.extend({
        /** @property */
        className: 'select-all-header-cell renderable',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        /** @property */
        container: '.oro-datagrid',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.selectState = options.selectState;

            this.listenTo(this.selectState, 'change', _.bind(_.debounce(this.updateState, 50), this));
            this.render();
        },

        /**
         * @inheritDoc
         */
        render: function() {
            BackendSelectAllHeaderCell.__super__.render.call(this);
            $(this.container).before(this.$el);

            return this;
        }
    });

    return BackendSelectAllHeaderCell;
});
