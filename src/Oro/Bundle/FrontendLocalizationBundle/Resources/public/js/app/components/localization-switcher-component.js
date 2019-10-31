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
         * @inheritDoc
         */
        constructor: function LocalizationSwitcherComponent(options) {
            LocalizationSwitcherComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
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
            const $el = $(e.target);

            const localization = $el.data('localization');
            if (localization !== this.options.selectedLocalization) {
                mediator.execute('showLoading');
                $.post(
                    routing.generate(this.options.localizationSwitcherRoute),
                    {localization: localization},
                    function() {
                        mediator.execute('refreshPage');
                    }
                );
            }
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
