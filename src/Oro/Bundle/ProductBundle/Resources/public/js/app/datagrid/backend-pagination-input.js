define(function(require) {
    'use strict';

    var BackendPaginationInput;
    var _ = require('underscore');
    var PaginationInput = require('orodatagrid/js/datagrid/pagination-input');

    BackendPaginationInput = PaginationInput.extend({
        themeOptions: {
            optionPrefix: 'backendpagination',
            el: '[data-grid-pagination]'
        },

        makeHandles: function(handles) {
            handles = BackendPaginationInput.__super__.makeHandles.apply(this, arguments);

            _.each(handles, function(index) {
                var $arrow = this.$el.find('[data-grid-pagination-direction=' + index.direction + ']');

                if ($arrow.length) {
                    if (index.className || !this.enabled) {
                        $arrow.addClass('disabled');
                    } else {
                        $arrow.removeClass('disabled');
                    }
                }
            }, this);

            return handles;
        },

        /**
         * @inheritDoc
         */
        onChangePage: function(e) {
            var obj = {};
            e.preventDefault();
            this.collection.trigger('backgrid:checkUnSavedData', obj);

            if (obj.live) {
                BackendPaginationInput.__super__.onChangePage.apply(this, arguments);
            }
        },

        /**
         * @inheritDoc
         */
        onChangePageByInput: function(e) {
            var obj = {};
            this.collection.trigger('backgrid:checkUnSavedData', obj);

            if (obj.live) {
                BackendPaginationInput.__super__.onChangePageByInput.apply(this, arguments);
            }
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var state = this.collection.state;
            var totalPages = state.totalPages || 1;

            // prevent render if data is not loaded yet
            if (state.totalRecords === null) {
                return this;
            }

            this.makeHandles();

            this.$el.find('[data-grid-pagination-pages]').text(totalPages);
            this.$el.find('[data-grid-pagination-records]').text(state.totalRecords);
            this.$('input')
                .val(state.firstPage === 0 ? state.currentPage + 1 : state.currentPage)
                .attr('disabled', !this.enabled || !state.totalRecords)
                .numeric({decimal: false, negative: false});

            if (this.hidden || totalPages === 1) {
                this.$el.hide();
            } else {
                this.$el.show();
            }

            return this;
        }
    });

    return BackendPaginationInput;
});
