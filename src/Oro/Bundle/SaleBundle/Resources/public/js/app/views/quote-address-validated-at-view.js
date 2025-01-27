import BaseAddressValidatedAtView from 'oroaddressvalidation/js/app/views/base-address-validated-at-view';
import AddressValidationDialogWidget from 'oroaddressvalidation/js/app/views/address-validation-dialog-widget';
import mediator from 'oroui/js/mediator';
import $ from 'jquery';

const QuoteAddressValidatedAtView = BaseAddressValidatedAtView.extend({
    /**
     * @property {jQuery.Element|null}
     */
    $customer: null,

    /**
     * @property {jQuery.Element|null}
     */
    $customerUser: null,

    /**
     * @property {jQuery.Element|null}
     */
    $customerAddress: null,

    /**
     * @property {Boolean}
     */
    delayedSubmit: false,

    /**
     * @property {Boolean}
     */
    delayedDialog: false,

    /**
     *
     * @param options
     */
    constructor: function QuoteAddressValidatedAtView(options) {
        QuoteAddressValidatedAtView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        QuoteAddressValidatedAtView.__super__.initialize.call(this, options);

        this.$customer = this.$form.find('[data-name="field__customer"]');
        this.$customerUser = this.$form.find('[data-name="field__customer-user"]');
    },

    /**
     * @inheritDoc
     */
    createDialog() {
        return new AddressValidationDialogWidget({
            title: this.getDialogTitle(),
            url: this.dialogUrl,
            addressFormData: this.$addressForm.find(':input').serializeArray()
                .concat(this.$customer.serializeArray())
                .concat(this.$customerUser.serializeArray())
        });
    },

    /**
     * @inheritDoc
     */
    onFormSubmitDialogSuccess(event) {
        if (event.selectedAddressIndex === undefined) {
            return;
        }

        this.onDialogSuccess(event);

        // User selected in the Address Validation dialog the originally entered address.
        if (event.selectedAddressIndex === '0') {
            this.$form.trigger('submit');
        } else {
            // User selected in the Address Validation dialog one of the suggested addresses.

            this.listenToOnce(mediator, {'entry-point:quote:load:after': () => this.$form.trigger('submit')});
            mediator.trigger('entry-point:quote:trigger');
        }
    },

    /**
     * @inheritDoc
     */
    onDialogSuccess(event) {
        if (!event.addressForm) {
            return;
        }

        const $addressForm = $(event.addressForm);

        if (event.isAddressCreated || event.isAddressUpdated) {
            mediator.trigger('quote:loaded:related-data', {
                shippingAddress: $addressForm.find('[data-name="field__customer-address"]')
            });

            const $customerAddressEl = this._getCustomerAddressEl();

            $customerAddressEl.trigger('change');
        } else {
            this.updateAddressForm($addressForm);
        }

        if (event.selectedAddressIndex !== '0' && !event.isAddressCreated && !event.isAddressUpdated) {
            const $customerAddressEl = this._getCustomerAddressEl();

            // Selects "Enter other address" option.
            $customerAddressEl.val('0');
            $customerAddressEl.trigger('change');
        }

        // Triggers the entry point to update customer address dropdown.
        mediator.trigger('entry-point:quote:trigger');

        this.subview('dialog').remove();
    },

    /**
     * Opens the Address Validation dialog when customer address dropdown is changed.
     *
     * @private
     */
    _resolveDelayedDialog() {
        if (this.$validatedAt.val()) {
            return;
        }

        this.subview('dialog', this.createDialog());

        this.listenToOnce(this.subview('dialog'), {
            success: this.onDialogSuccess.bind(this),
            fail: this.onDialogFail.bind(this),
            reset: this.onDialogReset.bind(this),
            close: this.onDialogClose.bind(this)
        });

        this.subview('dialog').render();
    },

    /**
     * @inheritDoc
     */
    onAddressChange(event) {
        // Ensures that the validatedAt field is not cleared for the existing address - only for the new one.
        const $target = $(event.target);
        if (!this.$validatedAt.prop('disabled') && !$target.is('[data-name="field__customer-address"]')) {
            this.$validatedAt.val(null);
        }

        if ($target.is('[data-name="field__customer-address"]') && event.manually && (event.added || event.removed)) {
            const choiceValue = $target.val();
            if (!choiceValue || choiceValue === '0') {
                // User selected a new address, so address validation will be handled in onFormSubmit.
                return;
            }

            this.listenToOnce(mediator, {'entry-point:quote:load:after': this._resolveDelayedDialog.bind(this)});
            mediator.trigger('entry-point:quote:trigger');
        }
    },

    /**
     * Selects "Enter other address" option if user clicks on "Edit Address" in the Address Validation dialog.
     */
    onDialogReset() {
        const $customerAddressEl = this._getCustomerAddressEl();

        if ($customerAddressEl.val() || $customerAddressEl.val() !== '0') {
            // Selects "Enter other address" option.
            $customerAddressEl.val('0');
            $customerAddressEl.trigger('change');
        }
    },

    _getCustomerAddressEl() {
        return this.$addressForm.find('[data-name="field__customer-address"]');
    }
});

export default QuoteAddressValidatedAtView;
