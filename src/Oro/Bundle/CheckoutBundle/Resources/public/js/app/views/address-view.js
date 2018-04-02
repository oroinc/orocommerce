define(function(require) {
    'use strict';

    var AddressView;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/views/base/view');

    AddressView = BaseComponent.extend({
        options: {
            addedAddressOptionClass: 'option_added_address',
            selectors: {
                address: null,
                fieldsContainer: null,
                region: null,
                shipToBillingCheckbox: null,
                externalShipToBillingCheckbox: null
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function AddressView() {
            AddressView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);

            this.$addressSelector = this.$el.find(this.options.selectors.address);
            this.$fieldsContainer = this.$el.find(this.options.selectors.fieldsContainer);
            this.$regionSelector = this.$el.find(this.options.selectors.region);

            this.needCheckAddressTypes = this.options.selectors.shipToBillingCheckbox;
            if (this.needCheckAddressTypes) {
                this.typesMapping = this.$addressSelector.data('addresses-types');
                this.$shipToBillingCheckbox = this.$el.find(this.options.selectors.shipToBillingCheckbox);
                this.$shipToBillingCheckbox.on('change', _.bind(this._handleShipToBillingAddressCheckbox, this));
                this.shipToBillingContainer = this.$shipToBillingCheckbox.closest('fieldset');
                if (this.options.selectors.externalShipToBillingCheckbox) {
                    this.$externalShipToBillingCheckbox = $(this.options.selectors.externalShipToBillingCheckbox);
                }
            }

            this.$addressSelector.on('change', _.bind(this._onAddressChanged, this));
            this.$regionSelector.on('change', _.bind(this._onRegionListChanged, this));

            if (this.$fieldsContainer.find('.notification_error').length) {
                this.$fieldsContainer.removeClass('hidden');
            }

            this._onAddressChanged();
            this._handleShipToBillingAddressCheckbox();
            mediator.on('checkout:address:updated', this._onAddressUpdated, this);
            mediator.on('checkout:ship_to_checkbox:changed', this._onShipToCheckboxChanged, this);
        },

        _handleShipToBillingAddressCheckbox: function(e) {
            var disabled = false;
            if (this.needCheckAddressTypes) {
                disabled = this.$shipToBillingCheckbox.prop('checked') && this.$externalShipToBillingCheckbox;
            }

            if (!disabled) {
                this.$addressSelector.find('option.' + this.options.addedAddressOptionClass).remove();
            }

            var isFormVisible = this._isFormVisible();
            var showNewAddressForm = !disabled && isFormVisible;
            this._handleNewAddressForm(showNewAddressForm);

            if (!showNewAddressForm) {
                this.$addressSelector.focus();
            }

            var isSelectorNotAvailable = isFormVisible && this._isOnlyOneOption();

            this.$addressSelector.prop('disabled', disabled || isSelectorNotAvailable).inputWidget('refresh');

            mediator.trigger('checkout:ship_to_checkbox:changed', this.$shipToBillingCheckbox);
            if (isSelectorNotAvailable) {
                this.$addressSelector.inputWidget('dispose');
                this.$addressSelector.hide().attr('data-skip-input-widgets', true);
            }

            // if external checkbox exists - synchronize it
            if (this.$externalShipToBillingCheckbox) {
                this.$externalShipToBillingCheckbox.off('change');
                this.$externalShipToBillingCheckbox.prop('checked', disabled);
                this.$externalShipToBillingCheckbox.on(
                    'change',
                    _.bind(this._handleExternalShipToBillingAddressCheckbox, this)
                );
            }
        },

        /**
         * @param {Boolean} show
         * @private
         */
        _handleNewAddressForm: function(show) {
            if (!this.$externalShipToBillingCheckbox) {
                return;
            }

            if (show) {
                this._showForm();
            } else {
                this._hideForm(true);
            }
        },

        _handleExternalShipToBillingAddressCheckbox: function() {
            this.$shipToBillingCheckbox.prop(
                'checked',
                this.$externalShipToBillingCheckbox.prop('checked')
            ).trigger('change');
        },

        _onAddressChanged: function() {
            if (this._isFormVisible()) {
                this._showForm();
            } else {
                this._hideForm();
            }
            mediator.trigger('checkout:address:updated', this.$addressSelector);
        },

        _onAddressUpdated: function($addressSelector) {
            if ($addressSelector === this.$addressSelector) {
                return;
            }
            if (this.$addressSelector.prop('disabled') && this.$shipToBillingCheckbox.prop('checked')) {
                var addressValue = $addressSelector.val();
                var addressTitle = $addressSelector.find('option:selected').text();
                this.$addressSelector.val(addressValue);
                // if no value - add needed value
                if (this.$addressSelector.val() !== addressValue) {
                    var $addedAddress = this.$addressSelector.find('.' + this.options.addedAddressOptionClass);
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

        _showForm: function() {
            if (this.needCheckAddressTypes) {
                this.shipToBillingContainer.removeClass('hidden');
            }

            this.$fieldsContainer.removeClass('hidden');
        },

        _hideForm: function(showCheckbox) {
            if (this.needCheckAddressTypes) {
                if (showCheckbox || _.indexOf(this.typesMapping[this.$addressSelector.val()], 'shipping') > -1) {
                    this.shipToBillingContainer.removeClass('hidden');
                } else {
                    this.$shipToBillingCheckbox.prop('checked', false);
                    this.$shipToBillingCheckbox.trigger('change');
                    this.shipToBillingContainer.addClass('hidden');
                }
            }

            this.$fieldsContainer.addClass('hidden');
        },

        _setAddressSelectorState: function(state) {
            this.$addressSelector.prop('disabled', state).inputWidget('refresh');
        },

        _onRegionListChanged: function(e) {
            this.$regionSelector.inputWidget('refresh');
        }
    });

    return AddressView;
});
