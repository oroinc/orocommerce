/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ProductSidebarGridComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var tools = require('oroui/js/tools');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ProductSidebarGridComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            widgetAlias: 'products-grid-widget',
            widgetRouteParameters: {
                gridName: 'products-grid',
                renderParams: {
                    enableFullScreenLayout: true,
                    enableViews: false
                },
                renderParamsTypes: {
                    enableFullScreenLayout: 'bool',
                    enableViews: 'bool'
                }
            },
            sidebarItemSelector: '.sidebar-item'
        },

        /**
         * @property {Object}
         */
        listen: {
            'grid_load:complete mediator': 'onGridLoadComplete'
        },

        /**
         * @property {Object}
         */
        $container: null,

        /**
         * @property {Object}
         */
        gridCollection: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$container = options._sourceElement;

            mediator.on('product_sidebar:changed', this.onSidebarChange, this);
        },

        /**
         * @param {Object} collection
         */
        onGridLoadComplete: function(collection) {
            if (collection.inputName === this.options.widgetRouteParameters.gridName) {
                this.gridCollection = collection;

                var self = this;
                widgetManager.getWidgetInstanceByAlias(
                    this.options.widgetAlias,
                    function(widget) {
                        self._patchGridCollectionUrl(self._getQueryParamsFromUrl(widget.options.url));
                    }
                );
            }
        },

        /**
         *
         * @param {Object} data
         */
        onSidebarChange: function(data) {
            var params = _.extend(this._getCurrentUrlParams(), data.params);
            var widgetParams = _.extend(this.options.widgetRouteParameters, params);

            var pageUrl = location.pathname + '?' + encodeURIComponent(this._urlParamsToString(params)) + location.hash;
            history.pushState({}, document.title, pageUrl);

            this._patchGridCollectionUrl(params);

            widgetManager.getWidgetInstanceByAlias(
                this.options.widgetAlias,
                function(widget) {
                    widget.setUrl(routing.generate('oro_datagrid_widget', widgetParams));

                    if (data.widgetReload) {
                        widget.render();
                    } else {
                        mediator.trigger('datagrid:doReset:' + widgetParams.gridName);
                    }
                }
            );
        },

        /**
         * @param {Object} params
         * @private
         */
        _patchGridCollectionUrl: function(params) {
            var collection = this.gridCollection;
            if (!_.isUndefined(collection)) {
                var url = collection.url.substring(0, collection.url.indexOf('?'));
                var newParams = _.extend(this._getQueryParamsFromUrl(collection.url), params);

                collection.url = url + '?' + this._urlParamsToString(newParams);
            }
        },

        /**
         * @private
         * @return {Object}
         */
        _getCurrentUrlParams: function() {
            return this._queryStringToObject(location.search.slice(1));
        },

        /**
         * @param {String} query
         * @return {Object}
         * @private
         */
        _queryStringToObject: function(query) {
            return query.length ? tools.unpackFromQueryString(decodeURIComponent(query)) : {};
        },

        /**
         * @param {String} url
         * @returns {Object}
         * @private
         */
        _getQueryParamsFromUrl: function(url) {
            var params = {};

            if (url.indexOf('?') !== -1) {
                params = this._queryStringToObject(decodeURIComponent(url.substring(url.indexOf('?') + 1, url.length)));
            }

            return params;
        },

        /**
         * @param {Object} params
         * @return {String}
         * @private
         */
        _urlParamsToString: function(params) {
            return $.param(params);
        }
    });

    return ProductSidebarGridComponent;
});
