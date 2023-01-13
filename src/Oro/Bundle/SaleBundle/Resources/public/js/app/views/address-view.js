define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orosale/js/app/views/address-view
     * @extends oroui.app.views.base.View
     * @class orosale.app.views.AddressView
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

        events: {
            'click [name="oro_sale_quote[shippingAddress][customerAddress]"]': 'addressFormChange'
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
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        listen: {
            'quote:load:related-data mediator': 'loadingStart',
            'quote:loaded:related-data mediator': 'loadedRelatedData'
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

            this.initLayout().done(this.handleLayoutInit.bind(this));

            this.loadingMaskView = new LoadingMaskView({container: this.$el});
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            const self = this;

            this.ftid = this.$el.find('div[data-ftid]:first').data('ftid');

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

                mediator.trigger('entry-point:quote:init');
            }

            if (this.options.selectors.address) {
                this.setAddress(this.$el.find(this.options.selectors.address));

                this.$fields.each(function() {
                    const $field = $(this);
                    if ($field.data('select2')) {
                        $field.data('selected-data', $field.select2('val'));
                    }
                });
                this.customerAddressChange();
            }
        },

        /**
         * Loading form after on select address click
         */
        addressFormChange: function() {
            const self = this;
            this.$fields.each(function() {
                const $field = $(this);
                const name = self.normalizeName($field.data('ftid').replace(self.ftid + '_', ''));
                self.fieldsByName[name] = $field;
            });
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
                name[i] = name[i][0].toUpperCase() + name[i].substr(1);
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
            this.$address.change(function(e) {
                self.customerAddressChange(e);
            });
        },

        /**
         * Implement customer address change logic
         */
        customerAddressChange: function() {
            if (this.$address.val() && this.$address.val() !== this.options.enterManuallyValue) {
                this.$fields.each(function() {
                    const $field = $(this);

                    $field.prop('readonly', true).inputWidget('refresh');
                });

                const address = this.$address.data('addresses')[this.$address.val()] || null;
                if (address) {
                    const self = this;

                    _.each(address, function(value, name) {
                        if (_.isObject(value)) {
                            value = _.first(_.values(value));
                        }
                        const $field = self.fieldsByName[self.normalizeName(name)] || null;
                        // set new value only in case if it is different from exising (`null` is transformed to `''`)
                        if ($field && $field.val() !== (value || '')) {
                            $field.val(value);
                            if ($field.data('select2')) {
                                $field.data('selected-data', value).change();
                            }
                        }
                    });
                }
            } else {
                this.$fields.each(function() {
                    const $field = $(this);

                    $field.prop('readonly', false).inputWidget('refresh');
                });
                const $country = this.fieldsByName.country;
                if ($country) {
                    $country.trigger('redraw');
                }
            }
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
         * Reset address form state
         */
        resetAddressForm: function() {
            this.$fields.each(function() {
                const $field = $(this);
                $field.val('');
                $field.prop('readonly', true).inputWidget('refresh');
                if ($field.data('select2')) {
                    $field.data('selected-data', '').change();
                }
            });
        },

        /**
         * Reset address selector
         */
        resetAddressSelector: function() {
            this.$address.empty();
            this.$address.data('addresses', {});
            if (this.$address.data('select2')) {
                this.$address.data('selected-data', '').change();
            }
        },

        /**
         * Set customer address choices from order related data
         *
         * @param {Object} response
         */
        loadedRelatedData: function(response) {
            const address = response[this.options.type + 'Address'] || null;
            if (!address) {
                this.resetAddressSelector();
                this.resetAddressForm();
                this.loadingEnd();
                return;
            }

            const $oldAddress = this.$address;
            this.setAddress($(address));
            this.resetAddressForm();
            $oldAddress.parent()
                .trigger('content:remove')
                .empty()
                .append(this.$address);

            this.initLayout().done(this.loadingEnd.bind(this));
        }
    });

    return AddressView;
});
