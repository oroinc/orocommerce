define(function(require) {
    'use strict';

    var CustomerSelectionComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');

    CustomerSelectionComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            customerSelect: '.customer-customer-select input[type="hidden"]',
            customerUserSelect: '.customer-customeruser-select input[type="hidden"]',
            customerUserMultiSelect: '.customer-customeruser-multiselect input[type="hidden"]',
            customerRoute: 'oro_customer_customer_user_get_customer',
            errorMessage: 'Sorry, an unexpected error has occurred.'
        },

        /**
         * @property {Object}
         */
        $customerSelect: null,

        /**
         * @property {Object}
         */
        $customerUserSelect: null,

        /**
         * @property {Object}
         */
        $customerUserMultiSelect: null,

        /**
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = options._sourceElement;
            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.$customerSelect = this.$el.find(this.options.customerSelect);
            this.$customerUserSelect = this.$el.find(this.options.customerUserSelect);
            this.$customerUserMultiSelect = this.$el.find(this.options.customerUserMultiSelect);

            this.$el
                .on('change', this.options.customerSelect, _.bind(this.onCustomerChanged, this))
                .on('change', this.options.customerUserSelect, _.bind(this.onCustomerUserChanged, this))
                .on('change', this.options.customerUserMultiSelect, _.bind(this.onCustomerUserChanged, this))
            ;

            this.updateCustomerUserSelectData({'customer_id': this.$customerSelect.val()});
        },

        /**
         * Handle Customer change
         */
        onCustomerChanged: function() {
            this.$customerUserSelect.inputWidget('val', '');
            this.$customerUserMultiSelect.inputWidget('val', '');

            this.updateCustomerUserSelectData({'customer_id': this.$customerSelect.val()});
            this.triggerChangeCustomerUserEvent();
        },

        /**
         * Handle CustomerUser change
         *
         * @param {jQuery.Event} e
         */
        onCustomerUserChanged: function(e) {
            var customerId = this.$customerSelect.val();
            var customerUserId = _.first($(e.target).val());

            if (customerId || !customerUserId) {
                this.triggerChangeCustomerUserEvent();

                return;
            }

            var self = this;
            $.ajax({
                url: routing.generate(this.options.customerRoute, {'id': customerUserId}),
                type: 'GET',
                beforeSend: function() {
                    self.loadingMask.show();
                },
                success: function(response) {
                    self.$customerSelect.inputWidget('val', response.customerId || '');

                    self.updateCustomerUserSelectData({'customer_id': response.customerId});
                    self.triggerChangeCustomerUserEvent();
                },
                complete: function() {
                    self.loadingMask.hide();
                },
                error: function(xhr) {
                    self.loadingMask.hide();
                    messenger.showErrorMessage(__(self.options.errorMessage), xhr.responseJSON);
                }
            });
        },

        /**
         * @param {Object} data
         */
        updateCustomerUserSelectData: function(data) {
            this.$customerUserSelect.data('select2_query_additional_params', data);
            this.$customerUserMultiSelect.data('select2_query_additional_params', data);
        },

        triggerChangeCustomerUserEvent: function() {
            mediator.trigger('customer-customer-user:change', {
                customerId: this.$customerSelect.val(),
                customerUserId: this.$customerUserSelect.val()
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();

            CustomerSelectionComponent.__super__.dispose.call(this);
        }
    });

    return CustomerSelectionComponent;
});
