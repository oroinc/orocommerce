define(['jquery', 'underscore', 'orodatagrid/js/url-helper'], function($, _, UrlHelper) {
    'use strict';

    var DisplayOptions = function() {
        this.initialize.apply(this, arguments);
    };

    _.extend(DisplayOptions.prototype, {
        /**
         * @property {Grid}
         */
        datagrid: null,

        /**
         * @property {string}
         */
        displaySelector: null,

        /**
         * @param {Object} [options.grid] grid instance
         * @param {Object} [options.options] grid initialization options
         */
        initialize: function(options) {
            this.datagrid = options.grid;
            this.displaySelector = options.options.metadata.options.displayOptions.selector;

            this.datagrid.collection.on('reset', _.bind(this._addDatagridStateTo, this));
            this._addDatagridStateTo();
        },

        _addDatagridStateTo: function() {
            var self = this;
            $(this.displaySelector).find('a').each(function (index, aTagElement) {
                var aTag = $(aTagElement);
                var url = aTag.attr('href');
                var key = self.datagrid.collection.stateHashKey();
                var value = self.datagrid.collection.stateHashValue();
                aTag.attr('href', UrlHelper.addUrlParameter(url, key, value));
            });
        }
    });

    return {
        /**
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         */
        init: function(deferred, options) {
            options.gridPromise.done(function(grid) {
                var validation = new DisplayOptions({
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
