export default {
    processDatagridOptions: (deferred, options) => {
        options.metadata.options.renderMode = 'toggle-mode';
        options.metadata.options.hidePreviousOpenFilters = false;
        options.enableToggleFilters = false;
        options.filterContainerSelector = '[data-role="sidebar-filter-container"]';
        return deferred.resolve();
    },

    init: deferred => deferred.resolve()
};
