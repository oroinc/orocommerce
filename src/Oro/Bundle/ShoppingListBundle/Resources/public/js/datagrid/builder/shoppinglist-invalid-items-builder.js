const safeForLaterBuilder = {
    processDatagridOptions(deferred, options) {
        if (!options.metadata.extraActions) {
            options.metadata.extraActions = [];
        }

        options.metadata.extraActions.push({
            frontend_type: 'select-all-items'
        });

        return deferred.resolve();
    },

    /**
     * Init() function is required
     */
    init: deferred => deferred.resolve()
};

export default safeForLaterBuilder;
