define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const UPSTransportSettingsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            countrySelector: null,
            shippingServicesSelector: null,
            // Needed to hide shipping service selector on empty country
            shippingServicesClosestParentSelector: null,
            shippingServiceByCountryRoute: null
        },

        /**
         * @property {jquery} country
         */
        country: null,

        /**
         * @property {jquery} shippingServices
         */
        shippingServices: null,

        /**
         * @property {string} shippingServicesClosestParentSelector
         */
        shippingServicesHolder: null,

        /**
         * @property {string} shippingServiceByCountryRoute
         */
        shippingServiceByCountryRoute: null,

        /**
         * @inheritdoc
         */
        constructor: function UPSTransportSettingsComponent(options) {
            UPSTransportSettingsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$elem = options._sourceElement;

            this.country = $(options.countrySelector);

            this.shippingServices = $(options.shippingServicesSelector);
            this.shippingServicesHolder = this.shippingServices.closest(options.shippingServicesClosestParentSelector);

            this.shippingServiceByCountryRoute = options.shippingServiceByCountryRoute;

            this.loadingMaskView = new LoadingMaskView({container: this.shippingServicesHolder});

            this.$elem.find(this.country).on('change', this.onCountryChange.bind(this));

            this.hideShippingServiceIfCountryNotSelected();
        },

        onCountryChange: function() {
            const country = this.country.val();
            const self = this;

            this.hideShippingServiceIfCountryNotSelected();

            if (country) {
                $.ajax({
                    url: routing.generate(this.shippingServiceByCountryRoute, {code: country}),
                    type: 'GET',
                    beforeSend: function() {
                        self.shippingServicesHolder.show();
                        self.loadingMaskView.show();
                    },
                    success: function(json) {
                        self.shippingServices
                            .find('option')
                            .remove();
                        $(json).each(function(index, data) {
                            self.shippingServices
                                .append('<option value="' + data.id + '">' + data.description + '</option>');
                        });
                    },
                    complete: function() {
                        self.loadingMaskView.hide();
                    }
                });
            }
        },

        hideShippingServiceIfCountryNotSelected: function() {
            const country = this.country.val();
            if (!country) {
                this.shippingServicesHolder.hide();
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$elem.off();
            this.$elem.find(this.country).off();

            UPSTransportSettingsComponent.__super__.dispose.call(this);
        }
    });

    return UPSTransportSettingsComponent;
});
