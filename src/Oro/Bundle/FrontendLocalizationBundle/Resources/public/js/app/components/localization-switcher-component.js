define(function(require) {
    'use strict';

    var LocalizationSwitcherComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');

    LocalizationSwitcherComponent = BaseComponent.extend({
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
            var $el = $(e.target);

            var localization = $el.data('localization');
            if (localization !== this.options.selectedLocalization) {
                mediator.execute('showLoading');
                $.post(
                    routing.generate(this.options.localizationSwitcherRoute),
                    {'localization': localization},
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
