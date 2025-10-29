define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const UrlHelper = require('orodatagrid/js/url-helper').default;
    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const mediator = require('oroui/js/mediator');
    const Popper = require('popper').default;

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
            this.$el = options._sourceElement;

            this.$el.on('click', '[data-catalog-view-trigger]', this._onSwitch.bind(this));
        },

        /**
         * @inheritdoc
         */
        delegateListeners() {
            CatalogSwitchComponent.__super__.delegateListeners.call(this);

            this.$el.on('show.bs.dropdown', this._onShowDropdown.bind(this));
            this.$el.on('hide.bs.dropdown', this._onHideDropdown.bind(this));
        },

        _onSwitch: function(e) {
            if (location.search !== '') {
                e.preventDefault();

                const value = $(e.currentTarget).data('catalog-view-trigger');
                const url = this.updateUrlParameter(location.href, this.parameterName, value);
                mediator.execute('redirectTo', {url: url}, {redirect: true});
            }
        },

        _onShowDropdown: function(e) {
            this.destroyPopper();

            this.popper = new Popper(
                this.$el.find('[data-toggle="dropdown"]').get(0),
                this.$el.find('.dropdown-menu').get(0),
                {
                    placement: 'top-end',
                    modifiers: {
                        flip: {
                            fn(data, options) {
                                // Try to avoid page header
                                const pageHeader = document.querySelector('.page-header');
                                const pageHeaderBottom = pageHeader?.getBoundingClientRect()?.bottom;
                                options.padding = Math.max(pageHeaderBottom, 5);

                                return Popper.Defaults.modifiers.flip.fn(data, options);
                            }
                        }
                    }
                }
            );
        },

        _onHideDropdown: function() {
            this.destroyPopper();
        },

        destroyPopper: function() {
            if (this.popper) {
                this.popper.destroy();
                this.popper = null;
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
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.destroyPopper();
            CatalogSwitchComponent.__super__.dispose.call(this);
        }
    }));

    return CatalogSwitchComponent;
});
