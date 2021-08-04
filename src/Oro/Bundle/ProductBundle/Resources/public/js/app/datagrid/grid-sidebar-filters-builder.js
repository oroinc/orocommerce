import _ from 'underscore';
import $ from 'jquery';
import FilterItemsHintView from 'oroproduct/js/app/views/sidebar-filters/filter-items-hint-view';
import FilterExtraHintView from 'oroproduct/js/app/views/sidebar-filters/filter-extra-hint-view';
import FilterApplierComponent from 'oroproduct/js/app/components/sidebar-filters/filter-applier-component';
import filtersContainerTemplate from 'tpl-loader!oroproduct/templates/sidebar-filters/filters-container.html';

export default {
    processDatagridOptions(deferred, options) {
        options.filterContainerSelector = '[data-role="sidebar-filter-container"]';
        options.metadata.options.enableFiltersNavigation = false;
        if (!options.metadata.options.filtersManager) {
            options.metadata.options.filtersManager = {};
        }
        Object.assign(options.metadata.options.filtersManager, {
            outerHintContainer: `[data-hint-container="${options.gridName}"]`,
            renderMode: 'toggle-mode',
            autoClose: false,
            enableMultiselectWidget: true,
            template: filtersContainerTemplate
        });

        options.metadata.filters.forEach(filter => {
            filter.outerHintContainer = `[data-hint-container="${options.gridName}"]`;
            filter.initiallyOpened = true;
            filter.autoClose = false;
            filter.labelPrefix = '';
            filter.animationDuration = 300;
        });

        const toolbarOptions = options.metadata.options.toolbarOptions;
        const toolbarClassNames = ['datagrid-toolbar--no-x-offset'];

        if (toolbarOptions.className) {
            toolbarClassNames.push(toolbarOptions.className);
        }
        toolbarOptions.className = _.uniq(toolbarClassNames).join(' ');

        return deferred.resolve();
    },

    init(deferred, options) {
        options.gridPromise.done(grid => {
            grid.once('filters:beforeRender', () => {
                const topToolbar = grid.toolbars.top;

                if (topToolbar && !topToolbar.disposed) {
                    const filterItemsHintView = new FilterItemsHintView({
                        renderMode: options.metadata.options.filtersManager.renderMode,
                        gridName: grid.name
                    });

                    $(topToolbar.el).after(filterItemsHintView.render().el);
                }
            });

            grid.once('filterManager:connected', () => {
                const filterManager = grid.filterManager;

                if (!Object.keys(filterManager.filters).length) {
                    return;
                }

                _.each(filterManager.filters, filter => {
                    filter.subview('sidebar-filters:extra-hint', new FilterExtraHintView({
                        filter: filter,
                        autoRender: true
                    }));
                });

                const applyFilterComponent = new FilterApplierComponent({
                    filterManager: filterManager
                });

                filterManager.subview('sidebar-filters:apply-filter-component', applyFilterComponent);
            });
        });
        return deferred.resolve();
    }
};
