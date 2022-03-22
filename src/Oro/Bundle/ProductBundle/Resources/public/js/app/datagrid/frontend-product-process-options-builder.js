import moduleConfig from 'module-config';

const config = {
    optimizedScreenSize: 'tablet',
    ...moduleConfig(module.id)
};

const frontendProductProcessOptionsBuilder = {
    processDatagridOptions: (deferred, options) => {
        options.metadata.options.optimizedScreenSize = config.optimizedScreenSize;

        deferred.resolve();
        return deferred;
    },

    /**
     * Init() function is required
     */
    init: (deferred, options) => {
        return deferred.resolve();
    }
};

export default frontendProductProcessOptionsBuilder;
