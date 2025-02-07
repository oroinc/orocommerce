import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';
import CheckoutAddressValidationDialogWidget from 'orocheckout/js/app/views/checkout-address-validation-dialog-widget';

const CheckoutSinglePageAddressValidationView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'dialogUrl',
        'addressType',
        'isBillingAddressValid'
    ]),

    /**
     * @property {string}
     */
    dialogUrl: '',

    /**
     * @property {string}
     */
    addressType: '',

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
    constructor: function CheckoutSinglePageAddressValidationView(options) {
        CheckoutSinglePageAddressValidationView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize: function(options) {
        CheckoutSinglePageAddressValidationView.__super__.initialize.call(this, options);

        this.$addressBook = this.$el.find('[data-addresses]');
        this.$form = this.$el.closest('form');
        this.$shipToBillingAddressCheckbox = this.$form.find('[data-name="field__ship-to-billing-address"]');
    },

    /**
     * @inheritDoc
     */
    delegateListeners() {
        CheckoutSinglePageAddressValidationView.__super__.delegateListeners.call(this);

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
        if (event.val === '0') {
            // User selected New Address.
            return;
        }

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
        if (this.addressType === 'shipping_address') {
            const isShipToBillingAddress = this.$shipToBillingAddressCheckbox.prop('checked');

            if (isShipToBillingAddress) {
                return !this.isBillingAddressValid;
            }
        }

        return this.$addressBook.val() !== '' &&
            !this.getAddressBookAddressData(this.$addressBook.val(), 'validatedAt');
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
                if (!autoSubmit) {
                    return;
                }

                this.$form.trigger('forceChange');
                mediator.once('single-page-checkout:after-save-data', () => {
                    this.$form.trigger('submit', [{skipValidation: true}]);
                });
            }
        });
    },

    onDialogClose() {
        this.removeSubview('dialog');
    }
});

export default CheckoutSinglePageAddressValidationView;
