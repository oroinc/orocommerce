define(function(require) {
    'use strict';

    var GridSidebarComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var tools = require('oroui/js/tools');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');

    GridSidebarComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            sidebarAlias: '',
            widgetAlias: '',
            widgetContainer: '',
            widgetRoute: 'oro_datagrid_widget',
            widgetRouteParameters: {
                gridName: ''
            }
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

            this.$container = options._sourceElement;
            this.$widgetContainer = $(options.widgetContainer);

            mediator.on('grid-sidebar:change:' + this.options.sidebarAlias, this.onSidebarChange, this);

            this.$container.find('.control-minimize').click(_.bind(this.minimize, this));
            this.$container.find('.control-maximize').click(_.bind(this.maximize, this));

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
            var self = this;

            this._pushState(params);

            this._patchGridCollectionUrl(params);

            widgetManager.getWidgetInstanceByAlias(
                this.options.widgetAlias,
                function(widget) {
                    widget.setUrl(routing.generate(self.options.widgetRoute, widgetParams));

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
            var paramsString = this._urlParamsToString(_.omit(params, 'saveState'));
            if (paramsString.length) {
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
                this.$container.addClass('grid-sidebar-maximized').removeClass('grid-sidebar-minimized');
                this.$widgetContainer.addClass('grid-sidebar-maximized').removeClass('grid-sidebar-minimized');

                delete params.sidebar;
            } else {
                this.$container.addClass('grid-sidebar-minimized').removeClass('grid-sidebar-maximized');
                this.$widgetContainer.addClass('grid-sidebar-minimized').removeClass('grid-sidebar-maximized');

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

            GridSidebarComponent.__super__.dispose.call(this);
        }
    });

    return GridSidebarComponent;
});
