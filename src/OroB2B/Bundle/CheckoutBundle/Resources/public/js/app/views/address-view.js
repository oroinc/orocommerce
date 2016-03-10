define(function(require) {
    'use strict';

    var AddressView;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/views/base/view');

    AddressView = BaseComponent.extend({

        /**
         * @property {jQuery.Element}
         */
        addressSelector: null,

        /**
         * @property {jQuery.Element}
         */
        regionSelector: null,

        /**
         * @property {jQuery.Element}
         */
        fieldsContainer: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.addressSelector = this.$el.find(options.selectors.address);
            this.fieldsContainer = this.$el.find(options.selectors.fieldsContainer);
            this.regionSelector = this.$el.find(options.selectors.region);

            this.addressSelector.on('change', _.bind(this.onAddressChanged, this));
            this.regionSelector.on('change', _.bind(this.onRegionListChanged, this));
        },

        onAddressChanged: function(e) {
            if (this.addressSelector.val() == 0) {
                this.fieldsContainer.removeClass('hidden');
            } else {
                this.fieldsContainer.addClass('hidden');
            }

        },

        onRegionListChanged: function(e) {
            this.regionSelector.chosen("destroy");
            this.regionSelector.chosen({disable_search_threshold: 10});
        }
    });

    return AddressView;
});
