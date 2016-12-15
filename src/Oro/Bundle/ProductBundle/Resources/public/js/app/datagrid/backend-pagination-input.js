define(function(require) {
    'use strict';

    var BackendPaginationInput;
    var _ = require('underscore');
    var PaginationInput = require('orodatagrid/js/datagrid/pagination-input');


    BackendPaginationInput =  PaginationInput.extend({
        themeOptions: {
            optionPrefix: 'backendpagination',
            el: '[data-grid-pagination]'
        },

        initialize: function(options) {
            BackendPaginationInput.__super__.initialize.apply(this, arguments);
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

            this.$('input').numeric({decimal: false, negative: false});

            return this;
        }
    });

    return BackendPaginationInput;
});
