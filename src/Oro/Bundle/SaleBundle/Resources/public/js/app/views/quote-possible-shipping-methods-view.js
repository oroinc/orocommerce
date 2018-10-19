define(function(require) {
    'use strict';

    var QuotePossibleShippingMethodsView;
    var $ = require('jquery');
    var PossibleShippingMethodsView = require('oroorder/js/app/views/possible-shipping-methods-view');

    QuotePossibleShippingMethodsView = PossibleShippingMethodsView.extend({
        /**
         * @inheritDoc
         */
        constructor: function QuotePossibleShippingMethodsView() {
            QuotePossibleShippingMethodsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            QuotePossibleShippingMethodsView.__super__.initialize.apply(this, arguments);
        },

        updatePossibleShippingMethods: function() {
            QuotePossibleShippingMethodsView.__super__.updatePossibleShippingMethods.apply(this, arguments);

            this.allowUnlistedAndLockFlags();
        },

        onShippingMethodTypeChange: function() {
            QuotePossibleShippingMethodsView.__super__.onShippingMethodTypeChange.apply(this, arguments);

            this.allowUnlistedAndLockFlags();
        },

        allowUnlistedAndLockFlags: function() {
            var $shippingMethodLockedFlag = $('[name$="[shippingMethodLocked]"]');
            var $allowUnlistedShippingMethodFlag = $('[name$="[allowUnlistedShippingMethod]"]');

            if ($shippingMethodLockedFlag.length <= 0 || $allowUnlistedShippingMethodFlag.length <= 0) {
                return;
            }

            var disableFlags = $('[name$="[estimatedShippingCostAmount]"]').val() <= 0;

            $shippingMethodLockedFlag.prop('disabled', disableFlags);
            $allowUnlistedShippingMethodFlag.prop('disabled', disableFlags);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            PossibleShippingMethodsView.__super__.dispose.call(this);
        }
    });

    return QuotePossibleShippingMethodsView;
});
