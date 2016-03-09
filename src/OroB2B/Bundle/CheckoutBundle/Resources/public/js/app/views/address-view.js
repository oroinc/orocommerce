define(function(require) {
    'use strict';

    var AddressView;
    var _ = require('underscore');
    var BaseComponent = require('orob2border/js/app/views/address-view');

    AddressView = BaseComponent.extend({

        /**
         * @property {jQuery.Element}
         */
        addressSelector: null,

        /**
         * @property {jQuery.Element}
         */
        fieldsContainer: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            AddressView.__super__.initialize.apply(this, options);

            this.addressSelector = this.$el.find(options.selectors.address);
            this.fieldsContainer = this.$el.find(options.selectors.fieldsContainer);

            this.addressSelector.on('change', _.bind(this.onAddressChanged, this));
        },

        onAddressChanged: function(e) {
            if (this.addressSelector.val() == 0) {
                this.fieldsContainer.removeClass('hidden');
            } else {
                this.fieldsContainer.addClass('hidden');
            }

        }
    });

    return AddressView;
});
