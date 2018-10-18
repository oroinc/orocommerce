define([
    'underscore',
    'oroui/js/mediator',
    'orodatagrid/js/datagrid/listener/abstract-listener'
], function(_, mediator, AbstractListener) {
    'use strict';

    var RelatedItemsListener;

    /**
     * @export  oroproduct/js/datagrid/listener/related-product-listener
     * @class   oroproduct.datagrid.listener.RelatedProductListener
     * @extends orodatagrid.datagrid.listener.AbstractListener
     */
    RelatedItemsListener = AbstractListener.extend({
        /**
         * @inheritDoc
         */
        constructor: function RelatedItemsListener() {
            RelatedItemsListener.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.grid = options.grid;

            mediator.on('change:' + this.grid.name, this.updateRelatedProductsGrid, this);
        },

        /**
         * Synchronize included and excluded values for grid, respectively by added and removed product ids
         */
        updateRelatedProductsGrid: function(addedProductsIds, removedProductsIds) {
            var collection = this.grid.collection;

            collection.trigger('setState', addedProductsIds, removedProductsIds);
            collection.fetch({reset: true});
        },

        /**
         * @inheritDoc
         */
        _processValue: function(id, model) {
            // it's not being used
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.grid;
            RelatedItemsListener.__super__.dispose.apply(this, arguments);
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
        var gridInitialization = options.gridPromise;

        gridInitialization.done(function(grid) {
            var listenerOptions = {
                $gridContainer: grid.$el,
                gridName: grid.name,
                grid: grid
            };

            var listener = new RelatedItemsListener(listenerOptions);
            deferred.resolve(listener);
        }).fail(function() {
            deferred.reject();
        });
    };

    return RelatedItemsListener;
});
