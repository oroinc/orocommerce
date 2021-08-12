define([
    'underscore',
    'oroui/js/mediator',
    'orodatagrid/js/datagrid/listener/abstract-listener'
], function(_, mediator, AbstractListener) {
    'use strict';

    /**
     * @export  oroproduct/js/datagrid/listener/related-product-listener
     * @class   oroproduct.datagrid.listener.RelatedProductListener
     * @extends orodatagrid.datagrid.listener.AbstractListener
     */
    const RelatedItemsListener = AbstractListener.extend({
        /**
         * @inheritdoc
         */
        constructor: function RelatedItemsListener(...args) {
            RelatedItemsListener.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.grid = options.grid;

            mediator.on('change:' + this.grid.name, this.updateRelatedProductsGrid, this);
        },

        /**
         * Synchronize included and excluded values for grid, respectively by added and removed product ids
         */
        updateRelatedProductsGrid: function(addedProductsIds, removedProductsIds) {
            const collection = this.grid.collection;

            collection.trigger('setState', addedProductsIds, removedProductsIds);
            collection.fetch({reset: true});
        },

        /**
         * @inheritdoc
         */
        _processValue: function(id, model) {
            // it's not being used
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.grid;
            RelatedItemsListener.__super__.dispose.call(this);
        }
    });

    /**
     * Builder interface implementation
     *
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     * @param {jQuery} [options.$el] container for the grid
     * @param {string} [options.gridName] grid name
     * @param {Object} [options.gridPromise] grid builder's promise
     * @param {Object} [options.data] data for grid's collection
     * @param {Object} [options.metadata] configuration for the grid
     */
    RelatedItemsListener.init = function(deferred, options) {
        const gridInitialization = options.gridPromise;

        gridInitialization.done(function(grid) {
            const listenerOptions = {
                $gridContainer: grid.$el,
                gridName: grid.name,
                grid: grid
            };

            const listener = new RelatedItemsListener(listenerOptions);
            deferred.resolve(listener);
        }).fail(function() {
            deferred.reject();
        });
    };

    return RelatedItemsListener;
});
