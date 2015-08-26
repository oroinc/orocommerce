define(function(require) {
    'use strict';

    var AddressView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var SubtotalsListener = require('orob2border/js/app/listener/subtotals-listener');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2border/js/app/views/address-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.AddressView
     */
    AddressView = BaseView.extend({
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
        useDefaultAddress: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.initLayout().done(_.bind(this.handleLayoutInit, this));

            this.loadingMaskView = new LoadingMaskView({container: this.$el});

            mediator.on('order:load:related-data', this.loadingStart, this);
            mediator.on('order:loaded:related-data', this.loadedRelatedData, this);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            var self = this;

            this.ftid = this.$el.find('div[data-ftid]:first').data('ftid');

            this.setAddress(this.$el.find(this.options.selectors.address));

            this.useDefaultAddress = true;
            this.$fields = this.$el.find(':input[data-ftid]').filter(':not(' + this.options.selectors.address + ')');
            this.fieldsByName = {};
            this.$fields.each(function() {
                var $field = $(this);
                if ($field.val().length > 0) {
                    self.useDefaultAddress = false;
                }
                var name = self.normalizeName($field.data('ftid').replace(self.ftid + '_', ''));
                self.fieldsByName[name] = $field;
            });

            this.accountAddressChange();

            if (this.options.selectors.subtotalsFields.length > 0) {
                SubtotalsListener.listen(this.$el.find(this.options.selectors.subtotalsFields.join(', ')));
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
            for (var i = 1, iMax = name.length; i < iMax; i++) {
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

            var self = this;
            this.$address.change(function() {
                self.useDefaultAddress = false;
                self.accountAddressChange();
            });
        },

        /**
         * Implement account address change logic
         */
        accountAddressChange: function() {
            if (this.$address.val() !== this.options.enterManuallyValue) {
                this.$fields.each(function() {
                    var $field = $(this);

                    if ($field.data('select2')) {
                        $field.select2('readonly', true);
                    } else {
                        $field.attr('readonly', true);
                    }
                });

                var address = this.$address.data('addresses')[this.$address.val()] || null;
                if (address) {
                    var self = this;

                    _.each(address, function(value, name) {
                        if (_.isObject(value)) {
                            value = _.first(_.values(value));
                        }
                        var $field = self.fieldsByName[self.normalizeName(name)] || null;
                        if ($field) {
                            $field.val(value);
                            if ($field.data('select2')) {
                                $field.data('selected-data', value).change();
                            }
                        }
                    });

                    SubtotalsListener.updateSubtotals();
                }
            } else {
                this.$fields.each(function() {
                    var $field = $(this);

                    if ($field.data('select2')) {
                        $field.select2('readonly', false);
                    } else {
                        $field.attr('readonly', false);
                    }
                });
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
         * Set account address choices from order related data
         *
         * @param {Object} response
         */
        loadedRelatedData: function(response) {
            var address = response[this.options.type + 'Address'] || null;
            if (!address) {
                this.loadingEnd();
                return;
            }

            var $oldAddress = this.$address;
            this.setAddress($(address));

            $oldAddress.parent().trigger('content:remove');
            $oldAddress.select2('destroy')
                .replaceWith(this.$address);

            if (this.useDefaultAddress) {
                this.$address.val(this.$address.data('default'));
                this.accountAddressChange();
            }

            this.initLayout().done(_.bind(this.loadingEnd, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order:load:related-data', this.loadingStart, this);
            mediator.off('order:loaded:related-data', this.loadedRelatedData, this);

            AddressView.__super__.dispose.call(this);
        }
    });

    return AddressView;
});
