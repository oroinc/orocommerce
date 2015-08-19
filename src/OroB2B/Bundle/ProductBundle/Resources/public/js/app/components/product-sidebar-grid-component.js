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
            widgetContent: '.product-index-content',
            widgetRouteParameters: {
                gridName: 'products-grid',
                renderParams: {
                    enableFullScreenLayout: 1,
                    enableViews: 0
                },
                renderParamsTypes: {
                    enableFullScreenLayout: 'int',
                    enableViews: 'int'
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
        $container: {},

        /**
         * @property {Object}
         */
        gridCollection: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = options._sourceElement;
            this.$widgetContent = $(options.widgetContent);

            mediator.on('product_sidebar:changed', this.onSidebarChange, this);

            this.$el.find('.control-minimize').click(_.bind(this.minimize, this));
            this.$el.find('.control-maximize').click(_.bind(this.maximize, this));

            this._maximizeOrMaximize(null);
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

            history.pushState({}, document.title, location.pathname + '?' + $.param(params) + location.hash);

            this._patchGridCollectionUrl(params);

            widgetManager.getWidgetInstanceByAlias(
                this.options.widgetAlias,
                function(widget) {
                    widget.setUrl(routing.generate('oro_datagrid_widget', widgetParams));

                    if (data.widgetReload) {
                        widget.render();
                    } else {
                        mediator.trigger('datagrid:doRefresh:' + widgetParams.gridName);
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
                var url = collection.url;
                if (url.indexOf('?') !== -1) {
                    url = url.substring(0, url.indexOf('?'));
                }
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
         * @private
         * @param {Object} params
         */
        _pushState: function(params) {
            var paramsString = this._urlParamsToString(params);
            if (paramsString.length > 0) {
                paramsString = '?' + paramsString;
            }

            history.pushState({}, document.title, location.pathname + paramsString + location.hash);
        },

        minimize: function() {
            this._maximizeOrMaximize('off');
        },

        maximize: function() {
            this._maximizeOrMaximize('on');
        },

        /**
         * @private
         * @param {string} state
         */
        _maximizeOrMaximize: function(state) {
            var params = this._getCurrentUrlParams();

            if (state === null) {
                state = params.sidebar || 'on';
            }

            if (state === 'on') {
                this.$el.find('.sidebar-minimized').hide();
                this.$el.find('.sidebar-maximized').show();
                this.$widgetContent.addClass('product-sidebar-maximized').removeClass('product-sidebar-minimized');

                delete params.sidebar;
            } else {
                this.$el.find('.sidebar-maximized').hide();
                this.$el.find('.sidebar-minimized').show();
                this.$widgetContent.addClass('product-sidebar-minimized').removeClass('product-sidebar-maximized');

                params.sidebar = state;
            }

            this._pushState(params);
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
         * @return {Object}
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
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.gridCollection;

            ProductSidebarGridComponent.__super__.dispose.call(this);
        }
    });

    return ProductSidebarGridComponent;
});
