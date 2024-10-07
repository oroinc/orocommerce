define(function(require) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');

    const AddressView = BaseView.extend({
        options: {
            addedAddressOptionClass: 'option_added_address',
            hideNewAddressForm: false,
            selectors: {
                address: null,
                fieldsContainer: null,
                region: null,
                shipToBillingCheckbox: null,
                externalShipToBillingCheckbox: null
            }
        },

        events: {
            forceChange: 'onForceChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function AddressView(options) {
            AddressView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);

            this.$addressSelector = this.$el.find(this.options.selectors.address);
            this.typesMapping = this.$addressSelector.data('addresses-types');
            this.$fieldsContainer = this.$el.find(this.options.selectors.fieldsContainer);
            this.$regionSelector = this.$el.find(this.options.selectors.region);
            this.$shipToBillingCheckbox = this.$el.find(this.options.selectors.shipToBillingCheckbox);
            this.shipToBillingContainer = this.$shipToBillingCheckbox.parent();

            this.$addressSelector.on('change', this._onAddressChanged.bind(this));
            this._onAddressChanged();
            this.$regionSelector.on('change', this._onRegionListChanged.bind(this));
            this._onRegionListChanged();

            const $option = this.$addressSelector.find('[value="0"]');
            $option.addClass('hide');
            this.enterManuallyOriginLabel = $option.text();
            this._changeEnterManualValueLabel();

            if (this.options.hideNewAddressForm) {
                this.$shipToBillingCheckbox.on('change', this._handleShipToBillingAddressCheckbox.bind(this));
                if (this.options.selectors.externalShipToBillingCheckbox) {
                    this.$externalShipToBillingCheckbox = $(this.options.selectors.externalShipToBillingCheckbox);
                    const $externalShipToBillingCheckboxContainer = this.$externalShipToBillingCheckbox.parent();
                    $externalShipToBillingCheckboxContainer.on('changeHiddenClass',
                        this._handleExternalShipToBillingAddressCheckboxContainer
                            .bind(this, $externalShipToBillingCheckboxContainer)
                    );
                }
            }
            this._handleShipToBillingAddressCheckbox();

            if (this.$fieldsContainer.find('.notification_error').length) {
                this.$fieldsContainer.removeClass('hidden');
            }

            mediator.on('checkout:address:updated', this._onAddressUpdated, this);
            mediator.on('checkout:ship_to_checkbox:changed', this._onShipToCheckboxChanged, this);
        },

        _handleShipToBillingAddressCheckbox: function(e) {
            const disabled = this.options.hideNewAddressForm ? this.$shipToBillingCheckbox.prop('checked') : false;
            const isFormVisible = this._isFormVisible();
            const showNewAddressForm = !disabled && isFormVisible;

            if (!showNewAddressForm) {
                this.$addressSelector.trigger('focus');
            }

            const isSelectorNotAvailable = isFormVisible && this._isOnlyOneOption();

            this.$addressSelector.prop('disabled', disabled || isSelectorNotAvailable).inputWidget('refresh');

            mediator.trigger('checkout:ship_to_checkbox:changed', this.$shipToBillingCheckbox);
            if (isSelectorNotAvailable) {
                this.$addressSelector.inputWidget('dispose');
                this.$addressSelector.hide().attr('data-skip-input-widgets', true);
                this.$addressSelector.siblings('label').parent().toggle(showNewAddressForm);
            }

            // if external checkbox exists - synchronize it
            if (this.$externalShipToBillingCheckbox) {
                this.$externalShipToBillingCheckbox.off('change');
                this.$externalShipToBillingCheckbox.prop('checked', disabled);
                this.$externalShipToBillingCheckbox.on(
                    'change',
                    this._handleExternalShipToBillingAddressCheckbox.bind(this)
                );
            }
        },

        onForceChange() {
            mediator.trigger('checkout:new-address-update');
        },

        /**
         * @param {string} val
         * @return boolean
         */
        isManual: function(val) {
            return parseInt(val) === 0;
        },

        _changeEnterManualValueLabel: function(customLabel) {
            if (this.isManual(this.$addressSelector.val())) {
                let newAddressLabel = this.$addressSelector.data('new-address-label');
                if (newAddressLabel) {
                    newAddressLabel = this.enterManuallyOriginLabel + ' (' + newAddressLabel + ')';
                }

                const label = customLabel || newAddressLabel;
                if (label) {
                    const $option = this.$el.find('[value="0"]');
                    $option.removeClass('hide');
                    $option.text(label);
                }

                this.$addressSelector.inputWidget('refresh');
            }
        },

        _handleExternalShipToBillingAddressCheckbox: function() {
            this.$shipToBillingCheckbox.prop(
                'checked',
                this.$externalShipToBillingCheckbox.prop('checked')
            ).trigger('change');
        },

        _handleExternalShipToBillingAddressCheckboxContainer: function($container) {
            if ($container.hasClass('hidden')) {
                this.shipToBillingContainer.addClass('hidden');
            } else {
                this.shipToBillingContainer.removeClass('hidden');
            }
        },

        _onAddressChanged: function() {
            mediator.trigger('checkout:address:updated', this.$addressSelector);
        },

        _onAddressUpdated: function($addressSelector) {
            if ($addressSelector === this.$addressSelector) {
                return;
            }
            if (this.$addressSelector.prop('disabled') && this.$shipToBillingCheckbox.prop('checked')) {
                const addressValue = $addressSelector.val();
                const addressTitle = $addressSelector.find('option:selected').text();
                this.$addressSelector.val(addressValue);
                // if no value - add needed value
                if (this.$addressSelector.val() !== addressValue) {
                    let $addedAddress = this.$addressSelector.find('.' + this.options.addedAddressOptionClass);
                    if (!$addedAddress.length) {
                        $addedAddress = $('<option/>').addClass(this.options.addedAddressOptionClass);
                        this.$addressSelector.append($addedAddress);
                    }
                    $addedAddress.attr('value', addressValue).text(addressTitle);
                    this.$addressSelector.val(addressValue);
                }
                this.$addressSelector.inputWidget('refresh');
            }
        },

        _onShipToCheckboxChanged: function($shipToCheckbox) {
            if (!$shipToCheckbox || ($shipToCheckbox === this.$shipToBillingCheckbox)) {
                return;
            }
            if ($shipToCheckbox.prop('checked')) {
                mediator.trigger('checkout:address:updated', this.$addressSelector);
            }
        },

        _isFormVisible: function() {
            return this.$addressSelector.val() === '0';
        },

        _isOnlyOneOption: function() {
            return this.$addressSelector[0].length === 1;
        },

        _onRegionListChanged: function(e) {
            this.$regionSelector.inputWidget('refresh');
        }
    });

    return AddressView;
});
