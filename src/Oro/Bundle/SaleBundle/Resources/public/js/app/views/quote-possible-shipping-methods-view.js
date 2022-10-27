define(function(require) {
    'use strict';

    const $ = require('jquery');
    const PossibleShippingMethodsView = require('oroorder/js/app/views/possible-shipping-methods-view');

    const QuotePossibleShippingMethodsView = PossibleShippingMethodsView.extend({
        /**
         * @inheritdoc
         */
        constructor: function QuotePossibleShippingMethodsView(options) {
            QuotePossibleShippingMethodsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            QuotePossibleShippingMethodsView.__super__.initialize.call(this, options);
        },

        updatePossibleShippingMethods: function(methods) {
            QuotePossibleShippingMethodsView.__super__.updatePossibleShippingMethods.call(this, methods);

            this.allowUnlistedAndLockFlags();
        },

        onShippingMethodTypeChange: function(event) {
            QuotePossibleShippingMethodsView.__super__.onShippingMethodTypeChange.call(this, event);

            this.allowUnlistedAndLockFlags();
        },

        allowUnlistedAndLockFlags: function() {
            const $shippingMethodLockedFlag = $('[name$="[shippingMethodLocked]"]');
            const $allowUnlistedShippingMethodFlag = $('[name$="[allowUnlistedShippingMethod]"]');

            if ($shippingMethodLockedFlag.length <= 0 || $allowUnlistedShippingMethodFlag.length <= 0) {
                return;
            }

            const disableFlags = $('[name$="[estimatedShippingCostAmount]"]').val() <= 0;

            $shippingMethodLockedFlag.prop('disabled', disableFlags);
            $allowUnlistedShippingMethodFlag.prop('disabled', disableFlags);
        },

        /**
         * @inheritdoc
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
