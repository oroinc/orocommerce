define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const UrlHelper = require('orodatagrid/js/url-helper');
    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const mediator = require('oroui/js/mediator');

    const CatalogSwitchComponent = BaseComponent.extend(_.extend({}, UrlHelper, {
        parameterName: null,

        /**
         * @inheritdoc
         */
        constructor: function CatalogSwitchComponent(options) {
            CatalogSwitchComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            CatalogSwitchComponent.__super__.initialize.call(this, options);

            this.parameterName = options.parameterName;

            options._sourceElement
                .on('click', '[data-catalog-view-trigger]', this._onSwitch.bind(this));
        },

        _onSwitch: function(e) {
            if (location.search !== '') {
                e.preventDefault();

                const value = $(e.currentTarget).data('catalog-view-trigger');
                const url = this.updateUrlParameter(location.href, this.parameterName, value);
                mediator.execute('redirectTo', {url: url}, {redirect: true});
            }
        },

        updateUrlParameter: function(url, param, value) {
            const urlSplited = url.split('?');
            let urlObject = {};

            if (urlSplited.length > 1) {
                urlObject = tools.unpackFromQueryString(urlSplited[1]);
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
