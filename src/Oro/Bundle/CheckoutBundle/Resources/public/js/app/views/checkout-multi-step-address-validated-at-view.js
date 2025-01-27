import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';
import CheckoutAddressValidationDialogWidget from 'orocheckout/js/app/views/checkout-address-validation-dialog-widget';

const CheckoutMultiStepAddressValidatedAtView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'dialogUrl',
        'enableBillingValidation',
        'enableShippingValidation',
        'step',
        'isBillingAddressValid'
    ]),

    /**
     * @property {string}
     */
    dialogUrl: '',

    /**
     * @property {string}
     */
    step: '',

    /**
     * @property {boolean}
     */
    enableBillingValidation: false,

    /**
     * @property {boolean}
     */
    enableShippingValidation: false,

    /**
     * @property {boolean}
     */
    isBillingAddressValid: false,

    /**
     * @property {jQuery.Element|null}
     */
    $addressForm: null,

    /**
     * @property {jQuery.Element|null}
     */
    $addressLabel: null,

    /**
     * @property {jQuery.Element|null}
     */
    $validatedAt: null,

    /**
     * @property {jQuery.Element|null}
     */
    $shipToBillingAddressCheckbox: null,

    /**
     * @inheritDoc
     */
    constructor: function CheckoutMultiStepAddressValidatedAtView(options) {
        CheckoutMultiStepAddressValidatedAtView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize: function(options) {
        CheckoutMultiStepAddressValidatedAtView.__super__.initialize.call(this, options);

        this.$addressForm = this.$el.closest('form');
        this.$addressLabel = this.$addressForm.find('[data-name="field__label"]');
        this.$validatedAt = this.$addressForm.find('[data-name="field__validated-at"]');
        this.$shipToBillingAddressCheckbox = this.$addressForm.find('[data-name="field__ship-to-billing-address"]');
    },

    /**
     * @inheritDoc
     */
    delegateListeners() {
        CheckoutMultiStepAddressValidatedAtView.__super__.delegateListeners.call(this);

        this.listenTo(mediator, 'checkout:before-submit', this._onCheckoutBeforeSubmit.bind(this));
        this.listenTo(this.$addressForm.find(':input'), {change: this._onAddressFormChange.bind(this)});
    },

    _onAddressFormChange(event) {
        if (this.$validatedAt.length && !$(event.target).is(':checkbox')) {
            this.$validatedAt.val(null);
        }
    },

    /**
     * @param {Object} eventData
     */
    _onCheckoutBeforeSubmit: function(eventData) {
        if (typeof eventData.extraData === 'object' && eventData.extraData.skipValidation) {
            return;
        }

        if (!this.isApplicable()) {
            return;
        }

        if (eventData.stopped) {
            return;
        }

        eventData.stopped = true;
        if (!_.isUndefined(eventData.event)) {
            eventData.event.preventDefault();
        }

        this.openAddressValidationDialog();
    },

    /**
     * @returns {boolean}
     */
    isApplicable() {
        if (!this.enableShippingValidation && !this.enableBillingValidation) {
            return false;
        }

        if (this.enableBillingValidation && this.step === 'enter_billing_address') {
            return !(this.$validatedAt.length && this.$validatedAt.val());
        }

        if (this.enableShippingValidation) {
            const isShipToBillingAddress = this.$shipToBillingAddressCheckbox.prop('checked');

            if (this.step === 'enter_shipping_address') {
                if (isShipToBillingAddress) {
                    return !this.isBillingAddressValid;
                }

                return !(this.$validatedAt.length && this.$validatedAt.val());
            }

            if (this.step === 'enter_billing_address' && isShipToBillingAddress) {
                return !(this.$validatedAt.length && this.$validatedAt.val());
            }
        }

        return false;
    },

    openAddressValidationDialog() {
        this.subview('dialog', new CheckoutAddressValidationDialogWidget({
            title: this.getDialogTitle(),
            url: this.dialogUrl,
            addressFormData: this.$addressForm.find(':input').serializeArray()
        }));

        this.listenToOnce(this.subview('dialog'), {
            success: event => {
                this.onDialogSuccess(event);
            },
            close: this.onDialogClose.bind(this)
        });

        this.subview('dialog').render();
    },

    /**
     * @returns {string}
     */
    getDialogTitle() {
        const label = this.$addressLabel.val();

        if (label !== '') {
            return __('oro.address_validation.frontend.dialog.title_long', {label: label});
        } else {
            return __('oro.address_validation.frontend.dialog.title_short');
        }
    },

    /**
     * @param {object} event
     */
    onDialogSuccess(event) {
        this.subview('dialog').remove();
        this.removeSubview('dialog');

        mediator.trigger('checkout:refresh', {
            layoutSubtreeCallback: () => {
                $('[data-role="checkout-content-main"]').one('content:initialized', function() {
                    if (event.selectedAddressIndex !== '0') {
                        // Skips auto submit because a suggested address is selected.
                        return;
                    }

                    // this.$addressForm cannot be used anymore at this point because it does not exist.
                    $(this).find('form').trigger('submit', [{skipValidation: true}]);
                });
            }
        });
    },

    onDialogClose() {
        this.removeSubview('dialog');
    }
});

export default CheckoutMultiStepAddressValidatedAtView;
