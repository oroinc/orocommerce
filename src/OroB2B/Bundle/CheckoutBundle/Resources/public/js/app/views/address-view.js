define(function(require) {
    'use strict';

    var AddressView;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/views/base/view');

    AddressView = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.addressSelector = this.$el.find(options.selectors.address);
            this.fieldsContainer = this.$el.find(options.selectors.fieldsContainer);
            this.regionSelector = this.$el.find(options.selectors.region);

            this.checkTypes = options.selectors.hasOwnProperty('shipToBillingCheckbox');
            if (this.checkTypes) {
                this.typesMapping = this.addressSelector.data('addresses-types');
                this.shipToBillingCheckbox = this.$el.find(options.selectors.shipToBillingCheckbox);
                this.shipToBillingContainer = this.shipToBillingCheckbox.closest('fieldset');
            }

            this.addressSelector.on('change', _.bind(this.onAddressChanged, this));
            this.regionSelector.on('change', _.bind(this.onRegionListChanged, this));

            if (this.fieldsContainer.find('.notification_error').length) {
                this.fieldsContainer.removeClass('hidden');
            }

            this.onAddressChanged();
        },

        onAddressChanged: function(e) {
            var selectedAddress = this.addressSelector.val();
            if (selectedAddress === '0') {
                if (this.checkTypes) {
                    this.shipToBillingContainer.removeClass('hidden');
                }

                this.fieldsContainer.removeClass('hidden');
            } else {
                if (this.checkTypes) {
                    if (_.indexOf(this.typesMapping[selectedAddress], 'shipping') > -1) {
                        this.shipToBillingContainer.removeClass('hidden');
                    } else {
                        this.shipToBillingCheckbox.prop('checked', false);
                        this.shipToBillingCheckbox.trigger('change');
                        this.shipToBillingContainer.addClass('hidden');
                    }
                }

                this.fieldsContainer.addClass('hidden');
            }

        },

        onRegionListChanged: function(e) {
            this.regionSelector.chosen('destroy');
            this.regionSelector.chosen({disable_search_threshold: 10});
        }
    });

    return AddressView;
});
