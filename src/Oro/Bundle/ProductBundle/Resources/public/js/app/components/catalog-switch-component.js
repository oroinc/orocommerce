define(function(require) {
    'use strict';

    var CatalogSwitchComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var UrlHelper = require('orodatagrid/js/url-helper');
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var mediator = require('oroui/js/mediator');

    CatalogSwitchComponent = BaseComponent.extend(_.extend({}, UrlHelper, {
        parameterName: null,

        initialize: function(options) {
            CatalogSwitchComponent.__super__.initialize.apply(this, arguments);

            this.parameterName = options.parameterName;

            options._sourceElement
                .on('click', '[data-catalog-view-trigger]', _.bind(this._onSwitch, this));
        },

        _onSwitch: function(e) {
            if (location.search !== '') {
                e.preventDefault();

                var value = $(e.currentTarget).data('catalog-view-trigger');
                var url = this.updateUrlParameter(location.href, this.parameterName, value);
                mediator.execute('redirectTo', {url: url}, {redirect: true});
            }
        },

        updateUrlParameter: function(url, param, value) {
            var urlSplited = url.split('?');
            var urlObject = {};

            if (urlSplited.length > 1) {
                var urlObject = tools.unpackFromQueryString(urlSplited[1]);
            }

            if (!urlObject[param]) {
                urlObject[param] = {};
            }

            _.extend(urlObject[param], value);
            urlSplited[1] = tools.packToQueryString(urlObject);

            return urlSplited.join('?');
        }
    }));

    return CatalogSwitchComponent;
});
