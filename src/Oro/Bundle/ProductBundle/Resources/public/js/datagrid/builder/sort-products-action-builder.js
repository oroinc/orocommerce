const sortProductsActionBuilder = {
    /**
     * Prepares Datagrid options for a sort order extra action
     *
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     * @param {Object} [options.gridBuildersOptions]
     * @param {Object} [options.gridBuildersOptions.sortProductsAction]
     * @param {Object} [options.metadata] configuration for the grid
     * @param {Object} [options.metadata.extraActions] list of defined extra actions
     */
    processDatagridOptions(deferred, options) {
        const extraActions = options.metadata.extraActions || {};
        const buildersOptions = options.gridBuildersOptions?.sortProductsAction || {};

        let datagrid;
        options.gridPromise.done(grid => datagrid = grid);

        Object.assign(extraActions, {
            sortProducts: {
                frontend_type: 'sort-products',
                onSortingComplete: sequenceOfChanges => {
                    if (datagrid && sequenceOfChanges.length) {
                        datagrid.refreshAction.execute();
                    }
                },
                ...buildersOptions
            }
        });
        options.metadata.extraActions = extraActions;

        deferred.resolve();
        return deferred;
    },

    /**
     * Init() function is required
     */
    init(deferred) {
        deferred.resolve();
    }
};

export default sortProductsActionBuilder;
