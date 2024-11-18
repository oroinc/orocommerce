define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');

    const SinglePageCheckoutAddressView = BaseView.extend({
        options: {
            entityId: null,
            entityClass: 'Oro\\Bundle\\CheckoutBundle\\Entity\\Checkout',
            jsDialogWidget: 'oro/dialog-widget',
            dialogRoute: 'oro_frontend_action_widget_form',
            dialogWidth: 1000,
            dialogHeight: 'auto',
            resizable: false,
            autoResize: true
        },

        enterManuallyOriginLabel: null,

        /**
         * @inheritdoc
         */
        events: {
            change: 'onChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function SinglePageCheckoutAddressView(options) {
            SinglePageCheckoutAddressView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            const $option = this.$el.find('[value="0"]');
            $option.addClass('hide');
            this.enterManuallyOriginLabel = $option.text();
            this._changeEnterManualValueLabel();

            SinglePageCheckoutAddressView.__super__.initialize.call(this, options);
        },

        /**
         * @param {jQuery.Event} event
         */
        onChange: function(event) {
            if (this.isManual(event.val)) {
                event.stopPropagation();
            } else {
                this._resetEnterManualValueLabel(event);
            }
        },

        onEnableState: function() {
            this._changeEnterManualValueLabel();
        },

        onToggleState: function(disable, value, label) {
            if (disable) {
                this.$el.prop('disabled', 'disabled');

                this.$el.val(value);
                this.$el.inputWidget('refresh');
                this._changeEnterManualValueLabel(label);
            } else {
                this.$el.prop('disabled', false);
            }
        },

        /**
         * @param {string} val
         * @return boolean
         */
        isManual: function(val) {
            return parseInt(val) === 0;
        },

        /**
         * @param {string} type
         * @return boolean
         */
        isAvailableShippingType: function(type) {
            const availableTypes = this.$el.data('addresses-types');

            return !availableTypes.hasOwnProperty(this.$el.val()) ||
                _.indexOf(availableTypes[this.$el.val()], type) > -1;
        },

        _changeEnterManualValueLabel: function(customLabel) {
            if (this.isManual(this.$el.val())) {
                let newAddressLabel = this.$el.data('new-address-label');
                if (newAddressLabel) {
                    newAddressLabel = this.enterManuallyOriginLabel + ' (' + newAddressLabel + ')';
                }

                const label = customLabel || newAddressLabel;
                if (label) {
                    const $option = this.$el.find('[value="0"]');
                    $option.removeClass('hide');
                    $option.text(label);
                }

                this.$el.inputWidget('refresh');
            }
        },

        /**
         * @param {jQuery.Event} event
         */
        _resetEnterManualValueLabel: function(event) {
            if (this.enterManuallyOriginLabel) {
                const $element = $(event.target);
                const $option = $element.find('[value="0"]');

                $option.text(this.enterManuallyOriginLabel);
                $option.addClass('hide');

                $element.inputWidget('refresh');
            }
        }
    });

    return SinglePageCheckoutAddressView;
});
