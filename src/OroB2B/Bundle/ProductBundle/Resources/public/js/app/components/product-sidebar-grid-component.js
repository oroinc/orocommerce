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
        $container: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$container = options._sourceElement;
            this.$widgetContent = $(options.widgetContent);

            mediator.on('product_sidebar:changed', this.onSidebarChange, this);

            this.$container.find('.controll-minimize').click(_.bind(this.minimize, this));
            this.$container.find('.controll-maximize').click(_.bind(this.maximize, this));

            this._maximizeOrMaximize(null);
        },

        /**
         *
         * @param {Object} data
         */
        onSidebarChange: function(data) {
            var params = _.extend(this._getCurrentUrlParams(), data.params);
            var widgetParams = _.extend(this.options.widgetRouteParameters, params);

            this._pushState(params);

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
         * @private
         * @return {Object}
         */
        _getCurrentUrlParams: function() {
            var query = location.search.slice(1);

            return query.length ? tools.unpackFromQueryString(query) : {};
        },

        /**
         * @private
         * @param {Object} params
         */
        _pushState: function(params) {
            var paramsString = $.param(params);
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
         * @param {string} sidebar
         */
        _maximizeOrMaximize: function(sidebar) {
            var params = this._getCurrentUrlParams();

            if (sidebar === null) {
                sidebar = params.sidebar || 'on';
            }

            if (sidebar === 'on') {
                this.$container.find('.sidebar-minimized').hide();
                this.$container.find('.sidebar-maximized').show();
                this.$widgetContent.addClass('product-sidebar-maximized').removeClass('product-sidebar-minimized');

                delete params.sidebar;
            } else {
                this.$container.find('.sidebar-maximized').hide();
                this.$container.find('.sidebar-minimized').show();
                this.$widgetContent.addClass('product-sidebar-minimized').removeClass('product-sidebar-maximized');

                params.sidebar = sidebar;
            }

            this._pushState(params);
        }
    });

    return ProductSidebarGridComponent;
});
