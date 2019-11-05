define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const FiltersEventsDispatcher = BaseComponent.extend({
        constructor: function FiltersEventsDispatcher(options) {
            FiltersEventsDispatcher.__super__.constructor.call(this, options);
        },

        /**
         * @property {Grid}
         */
        datagrid: null,

        /**
         * @param {Object} [options.grid] grid instance
         * @param {Object} [options.options] grid initialization options
         */
        initialize: function(options) {
            this.datagrid = options.grid;

            this.listenTo(this.datagrid.collection, 'sync', this.triggerFiltersUpdateEvent);
            this.listenTo(this.datagrid, 'filterManager:connected', this.triggerFiltersUpdateEvent);

            FiltersEventsDispatcher.__super__.initialize.call(this, options);
        },

        triggerFiltersUpdateEvent: function() {
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
                const validation = new FiltersEventsDispatcher({
                    grid: grid,
                    options: options
                });
                deferred.resolve(validation);
            }).fail(function() {
                deferred.reject();
            });
        }
    };
});
