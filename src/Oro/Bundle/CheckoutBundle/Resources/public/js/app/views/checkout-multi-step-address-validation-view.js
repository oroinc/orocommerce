import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';
import CheckoutAddressValidationDialogWidget from 'orocheckout/js/app/views/checkout-address-validation-dialog-widget';

const CheckoutMultiStepAddressValidationView = BaseView.extend({
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
    $form: null,

    /**
     * @property {jQuery.Element|null}
     */
    $addressBook: null,

    /**
     * @property {jQuery.Element|null}
     */
    $shipToBillingAddressCheckbox: null,

    /**
     * @inheritDoc
     */
    constructor: function CheckoutMultiStepAddressValidationView(options) {
        CheckoutMultiStepAddressValidationView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize: function(options) {
        CheckoutMultiStepAddressValidationView.__super__.initialize.call(this, options);

        this.$addressBook = this.$el.find('[data-addresses]');
        this.$form = this.$el.closest('form');
        this.$shipToBillingAddressCheckbox = this.$form.find('[data-name="field__ship-to-billing-address"]');
    },

    /**
     * @inheritDoc
     */
    delegateListeners() {
        CheckoutMultiStepAddressValidationView.__super__.delegateListeners.call(this);

        this.listenTo(this.$addressBook, {change: this._onAddressBookChange.bind(this)});
        this.listenTo(mediator, 'checkout:before-submit', this._onCheckoutBeforeSubmit.bind(this));
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

        this.openAddressValidationDialog(true);
    },

    /**
     * @param {jQuery.Event} event
     * @private
     */
    _onAddressBookChange(event) {
        if (!this.isApplicable()) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        this.openAddressValidationDialog(false);
    },

    /**
     * @returns {boolean}
     */
    isApplicable() {
        if (!this.enableShippingValidation && !this.enableBillingValidation) {
            return false;
        }

        if (this.enableBillingValidation && this.step === 'enter_billing_address') {
            return !this.isAddressBookAddressValid();
        }

        if (this.enableShippingValidation) {
            const isShipToBillingAddress = this.$shipToBillingAddressCheckbox.prop('checked');

            if (this.step === 'enter_shipping_address') {
                if (isShipToBillingAddress) {
                    return !this.isBillingAddressValid;
                }

                return !this.isAddressBookAddressValid();
            }

            if (this.step === 'enter_billing_address' && isShipToBillingAddress) {
                return !this.isAddressBookAddressValid();
            }
        }

        return false;
    },

    /**
     * @returns {boolean}
     */
    isAddressBookAddressValid() {
        return Boolean(this.getAddressBookAddressData(this.$addressBook.val(), 'validatedAt'));
    },

    /**
     * @param {string} addressId
     * @param {string} fieldName
     *
     * @returns {string|integer}
     */
    getAddressBookAddressData(addressId, fieldName) {
        const addresses = this.$addressBook.data('addresses');

        if (typeof addresses[addressId] !== 'undefined') {
            return addresses[addressId][fieldName];
        }

        return undefined;
    },

    /**
     * @param {boolean} autoSubmit
     */
    openAddressValidationDialog(autoSubmit) {
        this.subview('dialog', new CheckoutAddressValidationDialogWidget({
            addressFormData: this.$form.find(':input').serializeArray(),
            title: this.getDialogTitle(),
            url: this.dialogUrl
        }));

        this.listenToOnce(this.subview('dialog'), {
            success: event => {
                this.onDialogSuccess(event, autoSubmit);
            },
            close: this.onDialogClose.bind(this)
        });

        this.subview('dialog').render();
    },

    /**
     * @returns {string}
     */
    getDialogTitle() {
        const label = this.getAddressBookAddressData(this.$addressBook.val(), 'label');

        if (label && label !== '') {
            return __('oro.address_validation.frontend.dialog.title_long', {label: label});
        } else {
            return __('oro.address_validation.frontend.dialog.title_short');
        }
    },

    /**
     * @param {object} event
     * @param {boolean} autoSubmit
     */
    onDialogSuccess(event, autoSubmit) {
        this.subview('dialog').remove();
        this.removeSubview('dialog');

        mediator.trigger('checkout:refresh', {
            layoutSubtreeCallback: () => {
                $('[data-role="checkout-content-main"]').one('content:initialized', function() {
                    if (!autoSubmit) {
                        // Skips auto submit because the address validation is not triggered by next transition.
                        return;
                    }

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

export default CheckoutMultiStepAddressValidationView;
