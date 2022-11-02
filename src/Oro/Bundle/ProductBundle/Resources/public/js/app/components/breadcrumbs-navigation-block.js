define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');

    const BreadcrumbsNavigationBlock = BaseComponent.extend({
        /**
         * @property
         */
        $element: null,

        /**
         * @inheritdoc
         */
        constructor: function BreadcrumbsNavigationBlock(options) {
            BreadcrumbsNavigationBlock.__super__.constructor.call(this, options);
        },

        listen: {
            'datagrid_filters:update mediator': 'onFiltersUpdate'
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$element = options._sourceElement;

            BreadcrumbsNavigationBlock.__super__.initialize.call(this, options);
        },

        onFiltersUpdate: function(datagrid) {
            this.updateFiltersInfo(datagrid);
            this.updateSortingInfo(datagrid);
            this.updatePaginationInfo(datagrid);
        },

        /**
         * Updates the components inner content,
         * presenting categories path current filters state.
         *
         * @param {object} datagrid
         */
        updateFiltersInfo: function(datagrid) {
            const currentFilters = [];
            const iterator = function(filterName, filterDefinition) {
                if (filterDefinition.name === filterName && filterDefinition.visible) {
                    const hint = datagrid.filterManager.filters[filterDefinition.name].getState().hint;

                    currentFilters.push({
                        hint: hint,
                        label: filterDefinition.label
                    });
                }
            };

            for (const filterName in datagrid.collection.state.filters) {
                if (datagrid.collection.state.filters.hasOwnProperty(filterName)) {
                    datagrid.metadata.filters.forEach(iterator.bind(null, filterName));
                }
            }

            if (currentFilters.length === 0) {
                $('.filters-info', this.$element).html('');

                return;
            }

            const buildFilterString = function(filter) {
                return filter.label + ' ' + filter.hint;
            };

            const filtersStrings = [];

            currentFilters.forEach(function(filter) {
                filtersStrings.push(buildFilterString(filter));
            });

            const filtersString = '[' + filtersStrings.join(', ') + ']';

            $('.filters-info', this.$element).text(filtersString);
        },

        /**
         * Updates the components inner content,
         * presenting sorting information.
         *
         * @param {object} datagrid
         */
        updateSortingInfo: function(datagrid) {
            let info = __('oro.product.grid.navigation_bar.sorting.label');

            const sorter = datagrid.collection.state.sorters;
            let sorterLabel = '';
            let sorterDirection = '';

            for (const k in sorter) {
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
            let info = __('oro.product.grid.navigation_bar.pagination.label');
            const state = datagrid.collection.state;

            const start = (state.currentPage - 1) * state.pageSize + 1;
            const end = state.totalRecords < state.pageSize ? state.totalRecords : (state.currentPage) * state.pageSize;

            info = info.replace('%start%', start).replace('%end%', end).replace('%total%', state.totalRecords);

            $('.pagination-info', this.$element).html(info);
        }
    });

    return BreadcrumbsNavigationBlock;
});
