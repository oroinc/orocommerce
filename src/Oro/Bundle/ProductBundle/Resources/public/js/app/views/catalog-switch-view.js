define(function(require) {
    'use strict';

    var CatalogSwitchView;
    var BaseView = require('oroui/js/app/views/base/view');
    var UrlHelper = require('orodatagrid/js/url-helper');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    CatalogSwitchView = BaseView.extend(_.extend({}, UrlHelper, {
        parameterName: null,

        events: {
            'click [data-catalog-view-trigger]': '_onSwitch'
        },

        initialize: function(options) {
            CatalogSwitchView.__super__.initialize.apply(this, arguments);

            this.parameterName = options.parameterName;
        },

        _onSwitch: function(e) {
            if (location.search != '') {
                e.preventDefault();

                var value = $(e.currentTarget).data('catalog-view-trigger');
                
                var url = this.updateUrlParameter(location.href, this.parameterName, value);

                mediator.execute('redirectTo', {url: url}, {redirect: true});
            }
        },

        updateUrlParameter: function(url, param, value) {
            var regex = new RegExp("(" + encodeURIComponent(param) + "=)[^\&]+");

            if (!regex.test(url)) {
                return this.addUrlParameter(url, param, value);
            }

            return url.replace(regex, '$1' + value);
        }
    }));

    return CatalogSwitchView;
});
