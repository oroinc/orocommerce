define(function(require) {
    'use strict';

    var FiltersEventsDispatcher;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    FiltersEventsDispatcher = BaseComponent.extend({
        constructor: function FiltersEventsDispatcher() {
            FiltersEventsDispatcher.__super__.constructor.apply(this, arguments);
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

            FiltersEventsDispatcher.__super__.initialize.apply(this, arguments);
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
                var validation = new FiltersEventsDispatcher({
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
