define(function(require) {
    'use strict';

    const _ = require('underscore');
    const PaginationInput = require('orodatagrid/js/datagrid/pagination-input');

    const BackendPaginationInput = PaginationInput.extend({
        themeOptions: {
            optionPrefix: 'backendpagination',
            el: '[data-grid-pagination]'
        },

        /**
         * @inheritdoc
         */
        constructor: function BackendPaginationInput(options) {
            BackendPaginationInput.__super__.constructor.call(this, options);
        },

        makeHandles: function(handles) {
            handles = BackendPaginationInput.__super__.makeHandles.call(this, handles);

            _.each(handles, function(index) {
                const $arrow = this.$el.find('[data-grid-pagination-direction=' + index.direction + ']');

                if ($arrow.length) {
                    if (index.className || !this.enabled) {
                        $arrow.addClass('disabled').attr('tabindex', -1);
                    } else {
                        $arrow.removeClass('disabled').removeAttr('tabindex');
                    }
                }
            }, this);

            return handles;
        },

        /**
         * @inheritdoc
         */
        onChangePage: function(e) {
            const obj = {};
            e.preventDefault();
            this.collection.trigger('backgrid:checkUnSavedData', obj);

            if (obj.live) {
                BackendPaginationInput.__super__.onChangePage.call(this, e);
            }
        },

        /**
         * @inheritdoc
         */
        onChangePageByInput: function(e) {
            const obj = {};
            this.collection.trigger('backgrid:checkUnSavedData', obj);

            if (obj.live) {
                BackendPaginationInput.__super__.onChangePageByInput.call(this, e);
            }
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const state = this.collection.state;
            const totalPages = state.totalPages || 1;

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
