import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import CheckoutAddressValidationDialogWidget from 'orocheckout/js/app/views/checkout-address-validation-dialog-widget';

const CheckoutBaseNewAddressValidatedAtView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'dialogUrl',
        'enableBillingValidation',
        'enableShippingValidation',
        'addressType'
    ]),

    /**
     * @property {string}
     */
    dialogUrl: '',

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
    constructor: function CheckoutBaseNewAddressValidatedAtView(options) {
        CheckoutBaseNewAddressValidatedAtView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize: function(options) {
        CheckoutBaseNewAddressValidatedAtView.__super__.initialize.call(this, options);

        this.$addressForm = this.$el.closest('form');
        this.$addressLabel = this.$addressForm.find('[data-name="field__label"]');
        this.$validatedAt = this.$addressForm.find('[data-name="field__validated-at"]');
        this.$shipToBillingAddressCheckbox = this.$addressForm.find('[data-name="field__ship-to-billing-address"]');
    },

    /**
     * @inheritDoc
     */
    delegateListeners() {
        CheckoutBaseNewAddressValidatedAtView.__super__.delegateListeners.call(this);

        this.listenTo(this.$addressForm.find(':input'), {change: this._onAddressFormChange.bind(this)});
        this.listenTo(this.$addressForm, {submit: this._onSubmit.bind(this)});
    },

    _onAddressFormChange() {
        this.$validatedAt.val('');
    },

    /**
     * @param {jQuery.Event} event
     */
    _onSubmit: function(event) {
        if (!this.isApplicable()) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        this.openAddressValidationDialog();
    },

    /**
     * @returns {boolean}
     */
    isApplicable() {
        if (this.enableBillingValidation && this.addressType === 'billing_address') {
            return !this.isAddressValid();
        }

        if (this.enableShippingValidation) {
            if (this.addressType === 'shipping_address') {
                return !this.isAddressValid();
            }

            const isShipToBillingAddress = this.$shipToBillingAddressCheckbox.prop('checked');

            if (this.addressType === 'billing_address' && isShipToBillingAddress) {
                return !this.isAddressValid();
            }
        }

        return false;
    },

    /**
     * @returns {boolean}
     */
    isAddressValid() {
        if (!this.$validatedAt.val()) {
            this.$addressForm.validate();

            return !this.$addressForm.valid();
        }

        return true;
    },

    openAddressValidationDialog() {
        this.subview('dialog', new CheckoutAddressValidationDialogWidget({
            addressFormData: this.$addressForm.find(':input').serializeArray(),
            title: this.getDialogTitle(),
            url: this.dialogUrl
        }));

        this.listenToOnce(this.subview('dialog'), {
            success: this.onDialogSuccess.bind(this)
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
     * Updates the address form and closes the Address Validation dialog.
     *
     * @param {Object} event
     */
    onDialogSuccess(event) {
        if (!event.addressForm) {
            return;
        }

        let addressFormDataArray;

        if (event.selectedAddressIndex === '0') {
            addressFormDataArray = $(event.addressForm)
                .find('[data-name="field__validated-at"]')
                .serializeArray();
        } else {
            addressFormDataArray = $(event.addressForm)
                .find(':input')
                .not('[data-name="field__id"]')
                .serializeArray();
        }

        this.updateAddressForm(addressFormDataArray);

        this.subview('dialog').remove();
        this.removeSubview('dialog');

        this.$addressForm.trigger('submit');
    },

    /**
     * Updates the address form.
     *
     * @param {Array} formDataArray
     */
    updateAddressForm(formDataArray) {
        formDataArray.forEach(
            input => this.$addressForm.find(':input[name="' + input.name + '"]').val(input.value)
        );
    }
});

export default CheckoutBaseNewAddressValidatedAtView;
