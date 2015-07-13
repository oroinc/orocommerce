/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ProductSidebarGridComponent,
        _ = require('underscore'),
        $ = require('jquery'),
        mediator = require('oroui/js/mediator'),
        routing = require('routing'),
        tools = require('oroui/js/tools'),
        widgetManager = require('oroui/js/widget-manager'),
        BaseComponent = require('oroui/js/app/components/base/component');

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
                renderParamsTypes : {
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
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.$container = options._sourceElement;

            mediator.on('product_sidebar:changed', this.onSidebarChange, this);
        },

        /**
         *
         * @param {Object} data
         */
        onSidebarChange: function (data) {
            var params = _.extend(this._getCurrentUrlParams(), data.params);
            var widgetParams = _.extend(this.options.widgetRouteParameters, params);

            history.pushState({}, document.title, location.pathname + '?' + $.param(params) + location.hash);

            widgetManager.getWidgetInstanceByAlias(
                this.options.widgetAlias,
                function (widget) {
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
        _getCurrentUrlParams: function () {
            var query = location.search.slice(1);

            return query.length ? tools.unpackFromQueryString(query) : {};
        }
    });

    return ProductSidebarGridComponent;
});
