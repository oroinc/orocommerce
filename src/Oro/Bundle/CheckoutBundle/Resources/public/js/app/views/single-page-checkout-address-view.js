define(function(require) {
    'use strict';

    var SinglePageCheckoutAddressView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var BaseView = require('oroui/js/app/views/base/view');
    var ButtonManager = require('oroaction/js/button-manager');
    var $ = require('jquery');

    SinglePageCheckoutAddressView = BaseView.extend({
        options: {
            entityId: null,
            entityClass: 'Oro\\Bundle\\CheckoutBundle\\Entity\\Checkout',
            dialogRoute: 'oro_frontend_action_widget_form',
            dialogWidth: 1000,
            dialogHeight: 700
        },

        enterManuallyOriginLabel: null,

        operations: {
            billing: 'b2b_flow_checkout_single_page_new_billing_address',
            shipping: 'b2b_flow_checkout_single_page_new_shipping_address'
        },

        titles: {
            billing: __('oro.checkout.billing_address.label'),
            shipping: __('oro.checkout.shipping_address.label')
        },

        /**
         * @inheritDoc
         */
        events: {
            'change': 'onChange',
            'select2-selecting': 'onSelecting'
        },

        /**
         * @inheritDoc
         */
        constructor: function SinglePageCheckoutAddressView() {
            SinglePageCheckoutAddressView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.enterManuallyOriginLabel = this.$el.find('[value="0"]').text();
            this._changeEnterManualValueLabel();

            SinglePageCheckoutAddressView.__super__.initialize.apply(this, arguments);
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

        /**
         * @param {jQuery.Event} event
         */
        onSelecting: function(event) {
            var previousVal = $(event.target).val();
            if (this.isManual(event.val)) {
                this.openDialog(event, previousVal);
            }
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
         * @param {jQuery.Event} event
         * @param {string} previousVal
         */
        openDialog: function(event, previousVal) {
            var addressType = $(event.target).data('address-type');
            var dialogUrl = routing.generate(this.options.dialogRoute, {
                'operationName': this._operationName(addressType),
                'entityClass': this.options.entityClass,
                'entityId': this.options.entityId
            });

            var buttonManager = new ButtonManager({
                hasDialog: true,
                showDialog: true,
                hasForm: true,
                dialogUrl: dialogUrl,
                dialogOptions: {
                    title: this._title(addressType),
                    dialogOptions: {
                        width: this.options.dialogWidth,
                        height: this.options.dialogHeight,
                        close: _.bind(this.closeDialog, this, event, previousVal)
                    }
                }
            });

            buttonManager.execute(event);
        },

        /**
         * @param {jQuery.Event} event
         * @param {string} previousVal
         */
        closeDialog: function(event, previousVal) {
            $(event.target).val(previousVal);
            $(event.target).inputWidget('refresh');
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
            var availableTypes = this.$el.data('addresses-types');

            return !availableTypes.hasOwnProperty(this.$el.val()) ||
                _.indexOf(availableTypes[this.$el.val()], type) > -1;
        },

        _changeEnterManualValueLabel: function(customLabel) {
            if (this.isManual(this.$el.val())) {
                var newAddressLabel = this.$el.data('new-address-label');
                if (newAddressLabel) {
                    newAddressLabel = this.enterManuallyOriginLabel + ' (' + newAddressLabel + ')';
                }

                var label = customLabel || newAddressLabel;
                if (label) {
                    var $option = this.$el.find('[value="0"]');
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
                var $element = $(event.target);
                var $option = $element.find('[value="0"]');

                $option.text(this.enterManuallyOriginLabel);

                $element.inputWidget('refresh');
            }
        },

        /**
         * @param {string} type
         * @return string
         */
        _title: function(type) {
            if (this.titles.hasOwnProperty(type)) {
                return this.titles[type];
            }

            return this.titles.billing;
        },

        /**
         * @param {string} type
         * @return string
         */
        _operationName: function(type) {
            if (this.operations.hasOwnProperty(type)) {
                return this.operations[type];
            }

            return this.operations.billing;
        }
    });

    return SinglePageCheckoutAddressView;
});
