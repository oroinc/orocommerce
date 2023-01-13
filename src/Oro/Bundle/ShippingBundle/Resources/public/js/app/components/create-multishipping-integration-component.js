define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const CreateMultishippingIntegrationComponent = BaseComponent.extend({
        route: 'oro_shipping_create_multishipping_integration',

        /**
         * @inheritdoc
         */
        constructor: function CreateMultishippingIntegrationComponent(options) {
            CreateMultishippingIntegrationComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$button = options._sourceElement;
            this.$container = this.$button.closest('.control-group');

            this.initListeners();
        },

        initListeners: function() {
            this.$button.on('click', this.createMultishippingIntegration.bind(this));
        },

        createMultishippingIntegration: function() {
            mediator.execute('showLoading');

            $.ajax({
                type: 'POST',
                url: this.getUrl(),
                success: response => {
                    if (response) {
                        this.showMessage('error', response);
                    } else {
                        this.showMessage('success', 'oro.multi_shipping.integration.create.success');
                        this.clear();
                    }
                },
                errorHandlerMessage: false,
                error: () => {
                    this.showMessage('error', 'oro.multi_shipping.integration.create.error');
                },
                complete: () => {
                    mediator.execute('hideLoading');
                }
            });

            return false;
        },

        showMessage: function(type, message) {
            messenger.notificationFlashMessage(type, __(message));
        },

        getUrl: function() {
            return routing.generate(this.route);
        },

        clear: function() {
            this.$container.empty();
        }
    });

    return CreateMultishippingIntegrationComponent;
});
