define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const BaseView = require('oroui/js/app/views/base/view');
    const ButtonManager = require('oroaction/js/button-manager');
    const $ = require('jquery');

    const SinglePageCheckoutAddressView = BaseView.extend({
        options: {
            entityId: null,
            entityClass: 'Oro\\Bundle\\CheckoutBundle\\Entity\\Checkout',
            jsDialogWidget: 'orofrontend/js/app/components/frontend-dialog-widget',
            dialogClass: 'ui-dialog--frontend',
            dialogRoute: 'oro_frontend_action_widget_form',
            dialogWidth: 1000,
            dialogHeight: 'auto',
            resizable: false,
            autoResize: true,
            popupIcon: 'fa-map-marker',
            popupBadge: true
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
         * @inheritdoc
         */
        events: {
            'change': 'onChange',
            'select2-selecting': 'onSelecting'
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

            this.enterManuallyOriginLabel = this.$el.find('[value="0"]').text();
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

        /**
         * @param {jQuery.Event} event
         */
        onSelecting: function(event) {
            const previousVal = $(event.target).val();
            if (this.isManual(event.val)) {
                this.openDialog(event, previousVal);
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
         * @param {jQuery.Event} event
         * @param {string} previousVal
         */
        openDialog: function(event, previousVal) {
            const addressType = $(event.target).data('address-type');
            const dialogUrl = routing.generate(this.options.dialogRoute, {
                operationName: this._operationName(addressType),
                entityClass: this.options.entityClass,
                entityId: this.options.entityId
            });

            const buttonManager = new ButtonManager({
                hasDialog: true,
                showDialog: true,
                hasForm: true,
                dialogUrl: dialogUrl,
                jsDialogWidget: this.options.jsDialogWidget,
                onDialogResult: this.onDialogResult.bind(this, event, previousVal),
                dialogOptions: {
                    title: this._title(addressType),
                    dialogOptions: {
                        dialogClass: this.options.dialogClass,
                        width: this.options.dialogWidth,
                        height: this.options.dialogHeight,
                        resizable: this.options.resizable,
                        autoResize: this.options.autoResize
                    },
                    fullscreenViewOptions: {
                        popupLabel: this._title(addressType),
                        popupIcon: this.options.popupIcon,
                        popupBadge: this.options.popupBadge
                    }
                }
            });

            buttonManager.execute(event);
        },

        /**
         * @param {jQuery.Event} openDialogEvent
         * @param {string} previousVal
         * @param {object} dialogResultEvent
         */
        onDialogResult: function(openDialogEvent, previousVal, dialogResultEvent) {
            if (dialogResultEvent.result) {
                if (!$(openDialogEvent.target).hasClass('custom-address')) {
                    $(openDialogEvent.target).addClass('custom-address');
                }

                $(openDialogEvent.target).trigger('forceChange');
            } else {
                $(openDialogEvent.target).val(previousVal);
                $(openDialogEvent.target).inputWidget('refresh');
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
