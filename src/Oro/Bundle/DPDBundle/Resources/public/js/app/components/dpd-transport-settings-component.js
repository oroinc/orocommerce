/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var DPDTransportSettingsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    DPDTransportSettingsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            ratePolicySelector: 'select[name$="[transport][ratePolicy]"]',
            flatRatePriceValueSelector: 'input[name$="[transport][flatRatePriceValue]"]',
            ratesCsvSelector: 'input[name$="[transport][ratesCsv]"]',
            container: '.control-group'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$elem = options._sourceElement;

            this.loadingMaskView = new LoadingMaskView({container: this.$elem});
            this.ratePolicyElem = $(this.$elem).find(this.options.ratePolicySelector);
            this.flatRatePriceValueElem = $(this.$elem).find(this.options.flatRatePriceValueSelector);
            this.ratesCsvElem = $(this.$elem).find(this.options.ratesCsvSelector);

            $(this.ratePolicyElem).on('change', _.bind(this.onRatePolicyChange, this));
            $(this.ratePolicyElem).trigger('change');
        },

        onRatePolicyChange: function() {
            var ratePolicyValue = $(this.ratePolicyElem).val();
            var self = this;

            if (ratePolicyValue === '0') { //DPDTransport::FLAT_RATE_POLICY
                $(this.flatRatePriceValueElem).closest(self.options.container).show();
                $(this.ratesCsvElem).closest(self.options.container).hide();
            } else if (ratePolicyValue === '1') { //DPDTransport::TABLE_RATE_POLICY
                $(this.flatRatePriceValueElem).closest(self.options.container).hide();
                $(this.ratesCsvElem).closest(self.options.container).show();
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$elem.off();
            this.$elem.find(this.options.countrySelector).off();

            DPDTransportSettingsComponent.__super__.dispose.call(this);
        }
    });

    return DPDTransportSettingsComponent;
});
