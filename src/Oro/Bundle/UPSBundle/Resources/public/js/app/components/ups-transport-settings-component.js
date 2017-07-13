/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var UPSTransportSettingsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    UPSTransportSettingsComponent = BaseComponent.extend({
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
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$elem = options._sourceElement;

            this.country = $(options.countrySelector);

            this.shippingServices = $(options.shippingServicesSelector);
            this.shippingServicesHolder = this.shippingServices.closest(options.shippingServicesClosestParentSelector);

            this.shippingServiceByCountryRoute = options.shippingServiceByCountryRoute;

            this.loadingMaskView = new LoadingMaskView({container: this.shippingServicesHolder});

            this.$elem.find(this.country).on('change', _.bind(this.onCountryChange, this));

            this.hideShippingServiceIfCountryNotSelected();
        },

        onCountryChange: function() {
            var country = this.country.val();
            var self = this;

            this.hideShippingServiceIfCountryNotSelected();

            if (country) {
                $.ajax({
                    url: routing.generate(this.shippingServiceByCountryRoute, {'code': country}),
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
            var country = this.country.val();
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
