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
    var Error = require('oroui/js/error');

    UPSTransportSettingsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            countrySelector: 'select[name$="[transport][country]"]',
            shippingServicesSelector: 'select[name$="[transport][applicableShippingServices][]"]',
            container: '.control-group',
            route: 'oro_ups_country_shipping_services'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$elem = options._sourceElement;

            this.loadingMaskView = new LoadingMaskView({container: this.$elem});
            this.$elem.find(this.options.countrySelector)
                .on('change', _.bind(this.onCountryChange, this))
                .trigger('change');
        },

        onCountryChange: function() {
            var country = this.$elem.find(this.options.countrySelector).val();
            var selected = this.$elem.find(this.options.shippingServicesSelector).val();
            var self = this;

            if (country != '') {
                $.ajax({
                    url: routing.generate(this.options.route, {'code': country}),
                    type: 'GET',
                    beforeSend: function () {
                        self.loadingMaskView.show();
                    },
                    success: function (json) {
                        $(self.options.shippingServicesSelector)
                            .closest(self.options.container)
                            .show();
                        $(self.options.shippingServicesSelector)
                            .find('option')
                            .remove();
                        $(json).each(function (index, data) {
                            $(self.options.shippingServicesSelector)
                                .append('<option value="' + data.id + '">' + data.description + '</option>')
                                .val(selected);
                        });
                    },
                    complete: function () {
                        self.loadingMaskView.hide();
                    },
                    error: function (xhr) {
                        Error.handle({}, xhr, {enforce: true});
                    }
                });
            } else {
                $(self.options.shippingServicesSelector)
                    .closest(self.options.container)
                    .hide();
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$elem.off();
            this.$elem.find(this.options.countrySelector).off();

            UPSTransportSettingsComponent.__super__.dispose.call(this);
        }
    });

    return UPSTransportSettingsComponent;
});
