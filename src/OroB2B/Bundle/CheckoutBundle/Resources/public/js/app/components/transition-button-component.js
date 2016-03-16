/** @lends TransitionButtonComponent */
define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    var TransitionButtonComponent;

    TransitionButtonComponent = BaseComponent.extend({
        defaults: {
            transitionUrl: null,
            message: null,
            conditionMessages: [],
            enabled: true,
            hasForm: false,
            selectors: {
                checkoutSidebar: '[data-role="checkout-sidebar"]',
                checkoutContent: '[data-role="checkout-content"]'
            }
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.defaults, options);
            this.inProgress = false;

            this.$el = options._sourceElement;
            this.$el.on('click', _.bind(this.transit, this));
        },

        transit: function(e) {
            e.preventDefault();
            if (!this.options.enabled || this.inProgress) {
                return;
            }

            this.inProgress = true;
            mediator.execute('showLoading');

            var method = 'GET';
            var data = null;
            if (this.options.hasForm) {
                method = 'POST';
                data = this.$el.closest('form').serialize();
            }

            $.ajax({
                    url: this.options.transitionUrl,
                    method: method,
                    data: data
                })
                .done(_.bind(this.onSuccess, this))
                .fail(_.bind(this.onFail, this))
                .always(
                    _.bind(function() {
                        mediator.execute('hideLoading');
                        this.inProgress = false;
                    }, this)
                );
        },

        onSuccess: function(response) {
            if (response.hasOwnProperty('redirectUrl')) {
                mediator.execute('redirectTo', {url: response.redirectUrl});
            } else {
                var $response = $(response);
                $(this.defaults.selectors.checkoutSidebar)
                    .html($response.find(this.defaults.selectors.checkoutSidebar).html());
                $(this.defaults.selectors.checkoutContent)
                    .html($response.find(this.defaults.selectors.checkoutContent).html());

                mediator.trigger('checkout-content:updated');
            }
        },

        onFail: function() {
            // TODO: improve errors handling
            mediator.execute('showFlashMessage', 'error', 'Could not perform transition');
        }
    });

    return TransitionButtonComponent;
});
