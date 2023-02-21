const sortProductsActionBuilder = {
    /**
     * Prepares Datagrid options for a sort order extra action
     *
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     * @param {Object} [options.metadata] configuration for the grid
     * @param {Object} [options.metadata.extraActions] list of defined extra actions
     */
    processDatagridOptions(deferred, options) {
        const extraActions = options.metadata.extraActions || {};
        const buildersOptions = options.gridBuildersOptions?.sortProductsAction || {};
        Object.assign(extraActions, {
            sortProducts: {
                frontend_type: 'sort-products',
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
    init(deferred, options) {
        deferred.resolve();
    }
};

export default sortProductsActionBuilder;
