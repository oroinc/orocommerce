import _ from 'underscore';
import FilterItemsHintView from 'oroproduct/js/app/views/filter-items-hint-view';

const filtersContainerTemplate = require('tpl-loader!oroproduct/templates/sidebar-filters/filters-container.html');

export default {
    processDatagridOptions(deferred, options) {
        options.metadata.options.renderMode = 'toggle-mode';
        options.metadata.options.outerHintContainer = `[data-hint-container="${options.gridName}"]`;
        options.metadata.options.hidePreviousOpenFilters = false;
        options.metadata.options.filtersContainerTemplate = filtersContainerTemplate;
        options.metadata.options.enableMultiselectWidget = false;
        options.enableToggleFilters = false;
        options.filterContainerSelector = '[data-role="sidebar-filter-container"]';

        const toolbarOptions = options.metadata.options.toolbarOptions;
        const toolbarClassNames = ['datagrid-toolbar--no-x-offset'];

        if (toolbarOptions.className) {
            toolbarClassNames.push(toolbarOptions.className );
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
                        renderMode: options.metadata.options.renderMode,
                        gridName: grid.name
                    });

                    topToolbar.el.after(filterItemsHintView.render().el);
                }
            });
        });
        return deferred.resolve();
    }
};
