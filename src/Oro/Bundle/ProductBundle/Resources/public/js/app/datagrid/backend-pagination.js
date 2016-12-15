define(function(require) {
    'use strict';

    var BackendPagination;
    var Pagination = require('orodatagrid/js/datagrid/pagination-input');

    BackendPagination = Pagination.extend({
        /** @property */
        themeOptions: {
            optionPrefix: 'pagination',
            el: '[data-grid-pagination]'
        },

        initialize: function(options) {
            BackendPagination.__super__.initialize.call(this, options);
        },

        render: function() {
            var state = this.collection.state;

            // prevent render if data is not loaded yet
            if (state.totalRecords === null) {
                return this;
            }

            if (this.hidden) {
                this.$el.hide();
            }

            return this;
        }
    });

    return BackendPagination;
});
