define(function(require) {
    'use strict';

    var BreadcrumbsNavigationBlock;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');

    BreadcrumbsNavigationBlock = BaseComponent.extend({
        /**
         * @property
         */
        $element: null,

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.$element = options._sourceElement;

            mediator.on('datagrid_filters:update', $.proxy(this, 'updateFiltersInfo'));
            mediator.on('datagrid_filters:update', $.proxy(this, 'updateSortingInfo'));
            mediator.on('datagrid_filters:update', $.proxy(this, 'updatePaginationInfo'));

            BreadcrumbsNavigationBlock.__super__.initialize.apply(this, arguments);
        },

        /**
         * {@inheritDoc}
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            BreadcrumbsNavigationBlock.__super__.dispose.call(this);
        },

        /**
         * Updates the components inner content,
         * presenting categories path current filters state.
         *
         * @param {object} datagrid
         */
        updateFiltersInfo: function(datagrid) {
            var currentFilters = [];
            var filterState;

            for (var filterName in datagrid.collection.state.filters) {
                if (!datagrid.collection.state.filters.hasOwnProperty(filterName)) {
                    continue;
                }

                filterState = datagrid.collection.state.filters[filterName];
                // jshint loopfunc: true
                datagrid.metadata.filters.forEach(function(filterDefinition) {
                    if (filterDefinition.name === filterName && filterDefinition.visible) {
                        var choiceTypeName;

                        filterDefinition.choices.forEach(function(choiceDefinition) {
                            if (choiceDefinition.value === filterState.type) {
                                choiceTypeName = choiceDefinition.label;
                            }
                        });

                        var hint = datagrid.filterManager.filters[filterDefinition.name].getState().hint;

                        currentFilters.push({
                            hint: hint,
                            label: filterDefinition.label
                        });
                    }
                });
            }

            if (currentFilters.length === 0) {
                $('.filters-info', this.$element).html('');

                return;
            }

            var buildFilterString = function(filter) {
                return filter.label + ' ' + filter.hint;
            };

            var filtersStrings = [];

            currentFilters.forEach(function(filter) {
                filtersStrings.push(buildFilterString(filter));
            });

            var filtersString = '[' + filtersStrings.join(', ') + ']';

            $('.filters-info', this.$element).text(filtersString);
        },

        /**
         * Updates the components inner content,
         * presenting sorting information.
         *
         * @param {object} datagrid
         */
        updateSortingInfo: function(datagrid) {
            var info = __('oro.product.grid.navigation_bar.sorting.label');

            var sorter = datagrid.collection.state.sorters;
            var sorterLabel = '';
            var sorterDirection = '';

            for (var k in sorter) {
                if (sorter.hasOwnProperty(k)) {
                    sorterLabel = k;
                    sorterDirection = __('oro.product.grid.navigation_bar.sorting.' + (sorter[k] > 0 ? 'desc' : 'asc'));

                    break;
                }
            }

            info = info.replace('%column%', sorterLabel).replace('%direction%', sorterDirection);

            $('.sorting-info', this.$element).html(info);
        },

        /**
         * Updates the components inner content,
         * presenting pagination information.
         *
         * @param {object} datagrid
         */
        updatePaginationInfo: function(datagrid) {
            var info = __('oro.product.grid.navigation_bar.pagination.label');
            var state = datagrid.collection.state;

            var start = (state.currentPage - 1) * state.pageSize + 1;
            var end = state.totalRecords < state.pageSize ? state.totalRecords : (state.currentPage) * state.pageSize;

            info = info.replace('%start%', start).replace('%end%', end).replace('%total%', state.totalRecords);

            $('.pagination-info', this.$element).html(info);
        }
    });

    return BreadcrumbsNavigationBlock;
});
