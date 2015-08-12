define(function(require) {
    'use strict';

    var PaymentTermView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2border/js/app/views/payment-term-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.PaymentTermView
     */
    PaymentTermView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectionTemplate: ''
        },

        /**
         * @property {jQuery}
         */
        $input: null,

        /**
         * @property {jQuery}
         */
        inputChanged: false,

        /**
         * @property {number|null}
         */
        accountPaymentTerm: null,

        /**
         * @property {number|null}
         */
        accountGroupPaymentTerm: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, this.options, options || {});

            this.$input = this.$el.find('input.select2');
            this.selectionTemplate = _.template(this.options.selectionTemplate);

            this.configureInput();

            this.initLayout().done(_.bind(this.handleLayoutInit, this));

            mediator.on('order:loaded:related-data', this.loadedRelatedData, this);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            var self = this;

            this.accountPaymentTerm = this.parseInt(this.$input.data('account-payment-term'));
            this.accountGroupPaymentTerm = this.parseInt(this.$input.data('account-group-payment-term'));

            this.$input.change(function() {
                self.inputChanged = true;
            });
        },

        configureInput: function() {
            var self = this;

            var configs = this.$input.data('pageComponentOptions').configs;
            configs.selection_template = configs.result_template = function(data) {
                data.isAccountDefault = data.id === self.accountPaymentTerm;
                data.isAccountGroupDefault = data.id === self.accountGroupPaymentTerm;

                return self.selectionTemplate(data);
            };
        },

        parseInt: function(val) {
            return val ? parseInt(val, 10) : null;
        },

        /**
         * Set payment term value from order related data
         *
         * @param {Object} response
         */
        loadedRelatedData: function(response) {
            var paymentTermKeys = ['accountPaymentTerm', 'accountGroupPaymentTerm'];
            var paymentTerm;
            for (var i = 0, iMax = paymentTermKeys.length; i < iMax; i++) {
                paymentTerm = response[paymentTermKeys[i]] || null;
                if (paymentTerm) {
                    break;
                }
            }

            if (!paymentTerm || this.inputChanged) {
                return;
            }

            this.$input.select2('val', paymentTerm);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order:loaded:related-data', this.loadedRelatedData, this);

            PaymentTermView.__super__.dispose.call(this);
        }
    });

    return PaymentTermView;
});
