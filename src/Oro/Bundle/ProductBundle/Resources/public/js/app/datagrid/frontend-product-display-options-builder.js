define(function(require) {
    'use strict';

    const $ = require('jquery');
    const UrlHelper = require('orodatagrid/js/url-helper');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const DisplayOptions = BaseComponent.extend({
        constructor: function DisplayOptions(options) {
            DisplayOptions.__super__.constructor.call(this, options);
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

            DisplayOptions.__super__.initialize.call(this, options);
        },

        _addDatagridStateTo: function() {
            const self = this;
            $(this.displaySelector).find('a').each(function(index, aTagElement) {
                const aTag = $(aTagElement);
                const url = aTag.attr('href');
                const key = self.datagrid.collection.stateHashKey();
                const value = self.datagrid.collection.stateHashValue();
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
                const validation = new DisplayOptions({
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
