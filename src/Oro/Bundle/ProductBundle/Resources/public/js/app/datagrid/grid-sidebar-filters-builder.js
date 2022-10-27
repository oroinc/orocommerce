import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import FilterItemsHintView from 'oroproduct/js/app/views/sidebar-filters/filter-items-hint-view';
import FilterExtraHintView from 'oroproduct/js/app/views/sidebar-filters/filter-extra-hint-view';
import FilterApplierComponent from 'oroproduct/js/app/components/sidebar-filters/filter-applier-component';
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

        options.filtersStateElement = `[data-filters-state-container="${options.gridName}"]`;
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
            template: filtersContainerTemplate,
            fullscreenTemplate: filtersContainerFullscreenTemplate,
            defaultFiltersViewMode: FiltersManager.MANAGE_VIEW_MODE
        });
        options.metadata.filters.forEach(filter => {
            filter.outerHintContainer = `[data-hint-container="${options.gridName}"]`;
            filter.initiallyOpened = true;
            filter.autoClose = false;
            filter.labelPrefix = '';
            filter.animationDuration = 300;
        });

        return deferred.resolve();
    },

    init(deferred, options) {
        options.gridPromise.done(grid => {
            let applyFilterComponent;
            let filterItemsHintView;
            const topToolbar = grid.toolbars.top;
            const $stateContainer = $('<div></div>', {
                'data-filters-state-container': grid.name
            });
            const initExtraHits = filters => {
                // Add an extra hint only for rendered and visible filters
                for (const filter of filters) {
                    if (filter.$el.length === 0) {
                        continue;
                    }
                    filter.subview('sidebar-filters:extra-hint', new FilterExtraHintView({
                        filter: filter,
                        autoRender: true
                    }));
                }
            };
            const disposeExtraHits = filters => {
                for (const filter of filters) {
                    filter.removeSubview('sidebar-filters:extra-hint');
                }
            };
            const disposeApplyFilter = () => {
                if (applyFilterComponent && !applyFilterComponent.disposed) {
                    applyFilterComponent.dispose();
                }
            };

            $stateContainer.insertAfter(topToolbar.el);
            grid.once('filters:beforeRender', () => {
                if (topToolbar && !topToolbar.disposed) {
                    filterItemsHintView = new FilterItemsHintView({
                        renderMode: options.metadata.options.filtersManager.renderMode,
                        gridName: grid.name
                    });

                    $(topToolbar.el).after(filterItemsHintView.render().el);
                }

                const filterManager = grid.filterManager;

                filterManager.on('filters-render-mode-changed', ({isAsInitial}) => {
                    disposeApplyFilter();
                    disposeExtraHits(Object.values(filterManager.filters));

                    if (isAsInitial) {
                        applyFilterComponent = new FilterApplierComponent({filterManager});
                        initExtraHits(Object.values(filterManager.filters));
                    }
                });
            });
            grid.once('filterManager:connected', () => {
                const filterManager = grid.filterManager;

                if (!Object.keys(filterManager.filters).length) {
                    return;
                }

                initExtraHits(Object.values(filterManager.filters));

                applyFilterComponent = new FilterApplierComponent({filterManager});

                mediator.trigger(`${grid.name}-filters-in-sidebar:connected`, filterManager);
                filterManager.on('rendered', () => {
                    mediator.trigger(`${grid.name}-filters-in-sidebar:rendered`, filterManager);
                    filterManager.$el.one('remove', () => disposeApplyFilter());
                });
            });
            grid.once('dispose', () => {
                disposeApplyFilter();
                if (filterItemsHintView && !filterItemsHintView.disposed) {
                    filterItemsHintView.dispose();
                }

                $stateContainer.remove();
            });
        });
        return deferred.resolve();
    }
};
