define(function(require) {
    'use strict';

    var DisplayOptions;
    var $ = require('jquery');
    var UrlHelper = require('orodatagrid/js/url-helper');
    var BaseComponent = require('oroui/js/app/components/base/component');

    DisplayOptions = BaseComponent.extend({
        constructor: function DisplayOptions() {
            DisplayOptions.__super__.constructor.apply(this, arguments);
        },
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

            this.listenTo(this.datagrid.collection, 'reset', this._addDatagridStateTo);
            this._addDatagridStateTo();

            DisplayOptions.__super__.initialize.apply(this, arguments);
        },

        _addDatagridStateTo: function() {
            var self = this;
            $(this.displaySelector).find('a').each(function(index, aTagElement) {
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
