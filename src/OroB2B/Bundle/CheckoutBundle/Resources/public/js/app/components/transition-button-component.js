/** @lends TransitionButtonComponent */
define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    var TransitionButtonComponent;
    TransitionButtonComponent = BaseComponent.extend(/** @exports TransitionButtonComponent.prototype */{
        defaults: {
            transitionUrl: null,
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
            if (this.options.hasForm) {
                this.$form = this.$el.closest('form');
                this.$form.on('submit', _.bind(this.onSubmit, this));
            } else {
                this.$el.on('click', _.bind(this.transit, this));
            }
        },

        onSubmit: function(e) {
            this.transit(e, {method: 'POST', data: this.$form.serialize()});
        },

        transit: function(e, data) {
            e.preventDefault();
            if (!this.options.enabled || this.inProgress) {
                return;
            }

            this.inProgress = true;
            mediator.execute('showLoading');

            var url = this.options.transitionUrl;
            var widgetParameters = '_widgetContainer=ajax&_wid=ajax_checkout';
            url += (-1 !== _.indexOf(url, '?') ? '&' : '?') + widgetParameters;

            data = data || {method: 'GET'};
            data.url = url;
            $.ajax(data)
                .done(_.bind(this.onSuccess, this))
                .fail(_.bind(this.onFail, this))
                .always(function() {
                    mediator.execute('hideLoading');
                });
        },

        onSuccess: function(response) {
            this.inProgress = false;
            if (response.hasOwnProperty('redirectUrl')) {
                mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
            } else {
                var $response = $('<div/>').html(response);
                mediator.trigger('checkout-content:before-update');

                var $sidebar = $(this.defaults.selectors.checkoutSidebar);
                $sidebar.html($response.find(this.defaults.selectors.checkoutSidebar).html());

                var $content = $(this.defaults.selectors.checkoutContent);
                $content.html($response.find(this.defaults.selectors.checkoutContent).html());

                mediator.trigger('checkout-content:updated');
            }
        },

        onFail: function() {
            this.inProgress = false;
            mediator.execute('showFlashMessage', 'error', 'Could not perform transition');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('click', _.bind(this.transit, this));

            TransitionButtonComponent.__super__.dispose.call(this);
        }
    });

    return TransitionButtonComponent;
});
