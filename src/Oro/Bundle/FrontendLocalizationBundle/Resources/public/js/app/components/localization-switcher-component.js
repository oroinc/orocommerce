define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const $ = require('jquery');
    const routing = require('routing');

    const LocalizationSwitcherComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            localizationSwitcherRoute: 'oro_frontend_localization_frontend_set_current_localization',
            localizationElement: '[data-localization]',
            selectedLocalization: null,
            currentRoute: 'oro_frontend_root',
            currentRouteParameters: null,
            currentQueryParameters: null
        },

        /**
         * @inheritdoc
         */
        constructor: function LocalizationSwitcherComponent(options) {
            LocalizationSwitcherComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('click', this.options.localizationElement, this.onLocalizationChange.bind(this));
        },

        /**
         * @param {Event} e
         */
        onLocalizationChange: function(e) {
            e.preventDefault();
            const newLocalization = $(e.target).data('localization');
            const {selectedLocalization: initialLocalization} = this.options;

            mediator.execute('showLoading');
            this.syncActiveLocalization(newLocalization)
                // if localization is successfully changed,
                // page will redirect to a new page with appropriate slug if localized slug is in using.
                .then(function(data) {
                    if (!_.isUndefined(data.redirectTo)) {
                        const url = data.redirectTo;
                        mediator.execute('redirectTo', {url: url}, {redirect: true});
                    } else {
                        mediator.execute('showFlashMessage', 'error', 'Selected language is not enabled.');
                    }
                })
                .fail(() => {
                    // rollback selected localization if refresh was canceled
                    this.syncActiveLocalization(initialLocalization);
                });
        },

        syncActiveLocalization(localization) {
            const url = routing.generate(this.options.localizationSwitcherRoute);
            const {
                currentRoute: redirectRoute,
                currentRouteParameters: redirectRouteParameters,
                currentQueryParameters: redirectQueryParameters
            } = this.options;

            return $.post(url, {
                localization: localization,
                redirectRoute: redirectRoute,
                redirectRouteParameters: redirectRouteParameters,
                redirectQueryParameters: redirectQueryParameters
            }).always(() => mediator.execute('hideLoading'));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            LocalizationSwitcherComponent.__super__.dispose.call(this);
        }
    });

    return LocalizationSwitcherComponent;
});
