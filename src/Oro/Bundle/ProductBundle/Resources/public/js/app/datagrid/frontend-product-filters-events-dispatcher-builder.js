define(['jquery'], function($) {
    'use strict';

    var mediator = require('oroui/js/mediator');

    var FiltersEventsDispatcher = function() {
        this.initialize.apply(this, arguments);
    };

    _.extend(FiltersEventsDispatcher.prototype, {
        /**
         * @property {Grid}
         */
        datagrid: null,

        /**
         * @param {Object} [options.grid] grid instance
         * @param {Object} [options.options] grid initialization options
         */
        initialize: function (options) {
            this.datagrid = options.grid;

            this.datagrid.collection.on('sync', $.proxy(this, 'triggerFiltersUpdateEvent'));

            // trigger for first rendering
            this.triggerFiltersUpdateEvent();
        },

        triggerFiltersUpdateEvent: function () {
            mediator.trigger('datagrid_filters:update', this.datagrid);
        }
    });

    return {
        /**
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         */
        init: function(deferred, options) {
            options.gridPromise.done(function(grid) {
                var validation = new FiltersEventsDispatcher({
                    'grid': grid,
                    'options': options
                });
                deferred.resolve(validation);
            }).fail(function() {
                deferred.reject();
            });
        }
    };
});
