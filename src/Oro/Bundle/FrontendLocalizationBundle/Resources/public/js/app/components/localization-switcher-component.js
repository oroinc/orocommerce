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
            selectedLocalization: null
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
                .on('click', this.options.localizationElement, _.bind(this.onLocalizationChange, this));
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
                // try to refresh page if localization is successfully changed
                // and return promise of page refresh action result
                .then(() => mediator.execute('refreshPage'))
                .fail(() => {
                    // rollback selected localization if refresh was canceled
                    this.syncActiveLocalization(initialLocalization)
                        .done(() => mediator.execute('hideLoading'));
                });
        },

        syncActiveLocalization(localization) {
            const url = routing.generate(this.options.localizationSwitcherRoute);
            return $.post(url, {localization});
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
