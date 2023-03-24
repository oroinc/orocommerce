import $ from 'jquery';
import {unique, difference} from 'underscore';

const sortProductsCollectionActionBuilder = {
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
        const {productCollectionSelectors, ...buildersOptions} = options.gridBuildersOptions?.sortProductsAction || {};

        let datagrid;
        options.gridPromise.done(grid => datagrid = grid);

        Object.assign(extraActions, {
            sortProducts: {
                frontend_type: 'sort-products',
                onSortingComplete: sequenceOfChanges => {
                    if (datagrid && sequenceOfChanges.length) {
                        const removeProducts = sequenceOfChanges.reduce((memo, changes) => {
                            memo.push(...changes.removeProducts);
                            return memo;
                        }, []);

                        if (removeProducts.length && productCollectionSelectors) {
                            // update list of included
                            const $included = $(productCollectionSelectors.included);
                            const included = $included.val();
                            if (included) {
                                const newIncluded = difference(included.split(','), removeProducts).join(',');
                                if (newIncluded !== included) {
                                    $included
                                        .val(newIncluded)
                                        .change()
                                        .trigger('patchInitialState');
                                }
                            }

                            // update list of excluded
                            const $excluded = $(productCollectionSelectors.excluded);
                            const excluded = $excluded.val();
                            if (excluded) {
                                removeProducts.unshift(...excluded.split(','));
                            }
                            $excluded
                                .val(unique(removeProducts).join(','))
                                .change()
                                .trigger('patchInitialState');
                        }
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

export default sortProductsCollectionActionBuilder;
