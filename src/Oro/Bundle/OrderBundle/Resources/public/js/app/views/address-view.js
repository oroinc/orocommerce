import $ from 'jquery';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import BaseView from 'oroui/js/app/views/base/view';

/**
 * @export oroorder/js/app/views/address-view
 * @extends oroui.app.views.base.View
 * @class oroorder.app.views.AddressView
 */
const AddressView = BaseView.extend({
    /**
     * @property {Object}
     */
    options: {
        enterManuallyValue: '0',
        type: '',
        selectors: {
            address: '',
            subtotalsFields: []
        }
    },

    /**
     * @property {String}
     */
    ftid: '',

    /**
     * @property {jQuery}
     */
    $fields: null,

    /**
     * @property {jQuery}
     */
    $address: null,

    /**
     * @property {Boolean}
     */
    useDefaultAddress: true,

    /**
     * @property {Object}
     */
    fieldsByName: null,

    /**
     * @property {LoadingMaskView}
     */
    loadingMaskView: null,

    listen: {
        'customer-customer-user:change mediator': 'handleCustomerChange',
        'order:load:related-data mediator': 'loadingStart',
        'order:loaded:related-data mediator': 'loadedRelatedData',
        'entry-point:order:load:before mediator': '_onEntryPointOrderLoadBefore',
        'entry-point:order:load:after mediator': '_onEntryPointOrderLoadAfter'
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
        this.options = $.extend(true, {}, this.options, options || {});

        this.initLayout().then(this.handleLayoutInit.bind(this));

        this.loadingMaskView = new LoadingMaskView({container: this.$el});
    },

    /**
     * Handles customer change event and updates the address selection accordingly.
     *
     * Logic:
     * 1. If the order uses a manually added address, no changes are made.
     * 2. If the order uses an address belonging to the Customer, and during edit
     *    we add, remove, or change the Customer User, no changes are made.
     * 3. If the order uses an address belonging to the Customer, and during edit
     *    we change the Customer, the address remains the same but is marked
     *    as manually selected.
     * 4. If the order uses an address belonging to the Customer User, and during edit
     *    we change the Customer User, the address remains the same but is marked
     *    as manually selected.
     *
     * @param {Object} e - Event data containing customer and user change flags.
     */
    handleCustomerChange: function(e) {
        const isCustomerChanged = e.isCustomerChanged || false;
        const isCustomerUserChanged = e.isCustomerUserChanged || false;
        const isCustomerUserAddressSelected = this.isUserAddressSelected();

        if (!isCustomerChanged && isCustomerUserChanged && !isCustomerUserAddressSelected) {
            return;
        }

        this._toggleEditable(!isCustomerUserAddressSelected);
        this.$address.val(null).trigger('change');
    },

    isUserAddressSelected: function() {
        if (!this.$address || !this.$address.val()) {
            return false;
        }

        const selectedAddressId = this.$address.val();
        const addresses = this.$address.data('addresses') || {};
        const selectedAddress = addresses[selectedAddressId];

        if (!selectedAddress) {
            return false;
        }

        return Boolean(selectedAddress.frontendOwner && selectedAddress.frontendOwner.email);
    },

    /**
     * Doing something after loading child components
     */
    handleLayoutInit: function() {
        const self = this;

        this.ftid = this.$el.find('div[data-ftid]').first().data('ftid');

        this.useDefaultAddress = true;
        this.$fields = this.$el.find(':input[data-ftid]').filter(':not(' + this.options.selectors.address + ')');
        this.fieldsByName = {};
        this.$fields.each(function() {
            const $field = $(this);
            if ($field.val().length > 0) {
                self.useDefaultAddress = false;
            }
            const name = self.normalizeName($field.data('ftid').replace(self.ftid + '_', ''));
            self.fieldsByName[name] = $field;
        });

        if (this.options.selectors.subtotalsFields.length > 0) {
            _.each(this.options.selectors.subtotalsFields, function(field) {
                $(field).attr('data-entry-point-trigger', true);
            });

            mediator.trigger('entry-point:order:init');
        }

        if (this.options.selectors.address) {
            this.setAddress(this.$el.find(this.options.selectors.address));

            this.$fields.each(function() {
                const $field = $(this);
                if ($field.data('select2')) {
                    $field.data('selected-data', $field.select2('val'));
                }
            });
            this.customerAddressChange(undefined, true);
        } else {
            this._toggleEditable(false);
        }
    },

    /**
     * Convert name with "_" to name with upper case, example: some_name > someName
     *
     * @param {String} name
     *
     * @returns {String}
     */
    normalizeName: function(name) {
        name = name.split('_');
        for (let i = 1, iMax = name.length; i < iMax; i++) {
            if (name[i]) {
                name[i] = name[i][0].toUpperCase() + name[i].substr(1);
            }
        }
        return name.join('');
    },

    /**
     * Set new address element and bind events
     *
     * @param {jQuery} $address
     */
    setAddress: function($address) {
        this.$address = $address;

        const self = this;
        this.$address.on('change', function(e) {
            // Do not block default address usage if there is no default address or selected address is default
            if (self.useDefaultAddress &&
                self.$address.data('default') &&
                self.$address.data('default') !== self.$address.val()
            ) {
                self.useDefaultAddress = false;
            }
            self.customerAddressChange(e);
        });

        self.customerAddressChange(null, true);
    },

    /**
     * Implement customer address change logic
     */
    customerAddressChange: function(e, isInit) {
        if (this.$address.val() && this.$address.val() !== this.options.enterManuallyValue) {
            this._toggleEditable(false);

            if (isInit !== true) {
                const address = this.$address.data('addresses')[this.$address.val()] || null;
                if (address) {
                    this._fillAddressFields(address, e !== undefined);
                }
            }
        } else {
            this._toggleEditable(true);
            const $country = this.fieldsByName.country;
            if (isInit !== true && $country) {
                $country.trigger('redraw');
            }
        }
    },

    /**
     * @param {Object} address
     * @param {Boolean} triggerChange
     */
    _fillAddressFields: function(address, triggerChange = true) {
        const self = this;
        _.each(_.omit(address, ['id']), function(value, name) {
            if (_.isObject(value)) {
                value = _.first(_.values(value));
            }
            const $field = self.fieldsByName[self.normalizeName(name)] || null;
            // set new value only in case if it is different from exising (`null` is transformed to `''`)
            if ($field && $field.val() !== (value || '')) {
                $field.val(value);
                if ($field.data('select2')) {
                    $field.data('selected-data', value);
                    if (triggerChange) {
                        $field.trigger('change');
                    }
                }
            }
        });
    },

    _toggleEditable: function(mode) {
        this.$fields.each(function(index, element) {
            $(this).prop('disabled', !mode).inputWidget('refresh');
        });
    },

    /**
     * Show loading view
     */
    loadingStart: function() {
        this.loadingMaskView.show();
    },

    /**
     * Hide loading view
     */
    loadingEnd: function() {
        this.loadingMaskView.hide();
    },

    /**
     * Set customer address choices from order related data
     *
     * @param {Object} response
     */
    loadedRelatedData: function(response) {
        const address = response[this.options.type + 'Address'] || null;
        if (!address) {
            this.loadingEnd();
            return;
        }

        const $oldAddress = this.$address;
        this.setAddress($(address));

        $oldAddress.parent()
            .trigger('content:remove')
            .empty()
            .append(this.$address);

        const defaultAddress = this.$address.data('default');
        if (this.useDefaultAddress && defaultAddress && defaultAddress !== this.$address.val()) {
            this.$address.val(defaultAddress).trigger('change');
        }

        this.initLayout().then(this.loadingEnd.bind(this));
    },

    _onEntryPointOrderLoadBefore: function() {
        if (this.$address.val() && this.$address.val() !== this.options.enterManuallyValue) {
            this.loadingStart();
        }
    },

    _onEntryPointOrderLoadAfter: function() {
        this.loadingEnd();
    }
});

export default AddressView;
