define(function(require) {
    'use strict';

    var CheckoutRegionView;
    var RegionView = require('oroaddress/js/region/view');

    CheckoutRegionView = RegionView.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.target = $(options.target);
            this.$colRegion = this.target.closest('.col-region');

            CheckoutRegionView.__super__.initialize.call(this, options);
        },

        /**
         * Show/hide select 2 element
         *
         * @param {Boolean} display
         */
        displaySelect2: function(display) {
            CheckoutRegionView.__super__.displaySelect2.call(this, display);
            if (display) {
                this.$colRegion.show();
            } else {
                this.$colRegion.hide();
            }
        }
    });

    return CheckoutRegionView;
});
