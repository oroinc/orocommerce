import mediator from 'oroui/js/mediator';
import FiltersManager from 'orofilter/js/filters-manager';
import SidebarToggleFiltersAction from 'oroproduct/js/app/datagrid/actions/sidebar-toggle-filters-action';
import filtersContainerTemplate from 'tpl-loader!oroproduct/templates/sidebar-filters/filters-container.html';
import filtersContainerFullscreenTemplate from 'tpl-loader!orofilter/templates/filters-container.html';

export default {
    processDatagridOptions(deferred, options) {
        if (!options.metadata.options.toolbarOptions) {
            options.metadata.options.toolbarOptions = {};
        }
        options.metadata.options.toolbarOptions.customAction = {
            constructor: SidebarToggleFiltersAction
        };

        options.filterContainerSelector = '[data-role="sidebar-filter-container"]';
        options.metadata.options.enableFiltersNavigation = false;
        if (!options.metadata.options.filtersManager) {
            options.metadata.options.filtersManager = {};
        }
        Object.assign(options.metadata.options.filtersManager, {
            renderMode: 'toggle-mode',
            autoClose: false,
            enableMultiselectWidget: true,
            template: filtersContainerTemplate,
            fullscreenTemplate: filtersContainerFullscreenTemplate,
            defaultFiltersViewMode: FiltersManager.MANAGE_VIEW_MODE,
            enableScrollContainerShadow: true
        });
        options.metadata.filters.forEach(filter => {
            filter.initiallyOpened = false;
            filter.autoClose = false;
            filter.animationDuration = 300;
            filter.notAlignCriteria = true;
        });

        return deferred.resolve();
    },

    init(deferred, options) {
        options.gridPromise.done(grid => {
            grid.once('filterManager:connected', () => {
                const filterManager = grid.filterManager;

                if (!Object.keys(filterManager.filters).length) {
                    return;
                }

                mediator.trigger(`${grid.name}-filters-in-sidebar:connected`, filterManager);
                filterManager.on('rendered', () => {
                    mediator.trigger(`${grid.name}-filters-in-sidebar:rendered`, filterManager);
                });
            });
        });

        return deferred.resolve();
    }
};
