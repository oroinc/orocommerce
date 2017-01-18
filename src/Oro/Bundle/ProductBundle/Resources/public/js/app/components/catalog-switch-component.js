define(function(require) {
    'use strict';

    var CatalogSwitchView;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var UrlHelper = require('orodatagrid/js/url-helper');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    CatalogSwitchView = BaseComponent.extend(_.extend({}, UrlHelper, {
        parameterName: null,

        initialize: function(options) {
            CatalogSwitchView.__super__.initialize.apply(this, arguments);

            this.parameterName = options.parameterName;

            options._sourceElement
                .on('click', '[data-catalog-view-trigger]', _.bind(this._onSwitch, this));
        },

        _onSwitch: function(e) {
            if (location.search !== '') {
                e.preventDefault();

                var value = $(e.currentTarget).data('catalog-view-trigger');
                var url = this.addUrlParameter(location.href, this.parameterName, value) ;
                mediator.execute('redirectTo', {url: url}, {redirect: true});
            }
        }
    }));

    return CatalogSwitchView;
});
