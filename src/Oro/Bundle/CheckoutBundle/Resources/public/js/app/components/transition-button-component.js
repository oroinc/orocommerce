define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');
    var DISABLED_CLASS = 'btn--disabled';

    var TransitionButtonComponent;
    TransitionButtonComponent = BaseComponent.extend(/** @lends TransitionButtonComponent.prototype */{
        defaults: {
            transitionUrl: null,
            enabled: true,
            enableOnLoad: true,
            hasForm: false,
            flashMessageOnSubmit: null,
            selectors: {
                checkoutFlashNotifications: '[data-role="checkout-flash-notifications"]',
                checkoutSidebar: '[data-role="checkout-sidebar"]',
                checkoutContent: '[data-role="checkout-content"]',
                transitionTriggerContainer: '[data-role="transition-trigger-container"]',
                transitionTrigger: '[data-role="transition-trigger"]',
                stateToken: '[name$="[state_token]"]'
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function TransitionButtonComponent() {
            TransitionButtonComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.defaults, options);
            this.inProgress = false;
            this.$el = options._sourceElement;
            this.initializeTriggers();
            if (this.options.hasForm) {
                this.$form = this.$el.closest('form');
                this.$form.on('submit', $.proxy(this.onSubmit, this));
            } else {
                this.$el.on('click', $.proxy(this.transit, this));
            }

            if (!this.options.transitionUrl) {
                return;
            }

            if (this.options.enableOnLoad) {
                this.enableTransitionButton();
            }

            mediator.on('checkout:transition-button:enable', this.enableTransitionButton, this);
            mediator.on('checkout:transition-button:disable', this.disableTransitionButton, this);
        },

        enableTransitionButton: function() {
            if (this.options.enabled) {
                this.$el.removeAttr('disabled', false).removeClass(DISABLED_CLASS);
            }
        },

        disableTransitionButton: function() {
            this.$el.attr('disabled', 'disabled').addClass(DISABLED_CLASS);
        },

        initializeTriggers: function() {
            this.$transitionTriggers = this.$el
                .closest(this.options.selectors.transitionTriggerContainer)
                .find(this.options.selectors.transitionTrigger);

            this.$transitionTriggers.css('cursor', 'pointer');
            this.$transitionTriggers.on('click', $.proxy(this.transit, this));
        },

        onSubmit: function(e) {
            if (this.options.flashMessageOnSubmit) {
                e.preventDefault();
                mediator.execute('showFlashMessage', 'error', this.options.flashMessageOnSubmit);
                return false;
            }
            this.$form.validate();

            if (this.$form.valid()) {
                this.transit(e, {method: 'POST'});
            }
        },

        /**
         * @param {Event} e
         * @param {Object} data
         */
        transit: function(e, data) {
            e.preventDefault();
            if (!this.options.enabled || this.inProgress || !this.options.transitionUrl) {
                return;
            }

            this.inProgress = true;
            mediator.execute('showLoading');

            $.ajax(this.prepareAjaxData(data, this.options.transitionUrl))
                .done(_.bind(this.onSuccess, this))
                .fail(_.bind(this.onFail, this));
        },

        /**
         * @param {Object} data
         * @param {String} url
         * @returns {Object}
         */
        prepareAjaxData: function(data, url) {
            data = data || {method: 'GET'};
            data.url = url + (-1 !== _.indexOf(url, '?') ? '&' : '?') + '_widgetContainer=ajax&_wid=ajax_checkout';
            data.errorHandlerMessage = false;
            if (this.$form) {
                data.data = this.serializeForm();
            }

            return data;
        },

        /**
         * @returns {String}
         */
        serializeForm: function() {
            this.$form.find(this.options.selectors.stateToken)
                .prop('disabled', false)
                .removeAttr('disabled');
            return this.$form.serialize();
        },

        onSuccess: function(response) {
            this.inProgress = false;

            if (response.hasOwnProperty('responseData')) {
                var eventData = {stopped: false, responseData: response.responseData};
                // FIXME: Inconsistent event name. This is not place-order logic, just "Continue"
                mediator.trigger('checkout:place-order:response', eventData);
                if (eventData.stopped) {
                    return;
                }
            }

            if (response.hasOwnProperty('redirectUrl')) {
                mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
            } else {
                var $response = $('<div/>').html(response);
                var $title = $response.find('title');
                if ($title.length) {
                    document.title = $title.text();
                }
                var flashNotificationsSelector = this.options.selectors.checkoutFlashNotifications;
                var sidebarSelector = this.options.selectors.checkoutSidebar;
                var contentSelector = this.options.selectors.checkoutContent;

                mediator.trigger('checkout-content:before-update');

                var $sidebar = $(sidebarSelector);
                $sidebar.html($response.find(sidebarSelector).html());

                var $content = $(contentSelector);
                $content.html($response.find(contentSelector).html());

                var $flashNotifications = $response.find(flashNotificationsSelector);

                _.each($flashNotifications, function(element) {
                    var $element = $(element);
                    var type = $element.data('type');
                    var message = $element.data('message');
                    message = message.replace(/\n/g, '<br>');
                    _.delay(function() {
                        mediator.execute('showFlashMessage', type, message);
                    }, 100);
                });

                mediator.trigger('checkout-content:updated');
                mediator.trigger('layout:reposition');
            }

            mediator.execute('hideLoading');
        },

        onFail: function() {
            this.inProgress = false;
            mediator.execute('hideLoading');
            mediator.execute('showFlashMessage', 'error', 'Could not perform transition');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.$form) {
                this.$form.off('submit', $.proxy(this.onSubmit, this));
            }
            this.$el.off('click', $.proxy(this.transit, this));
            this.$transitionTriggers.off('click', $.proxy(this.transit, this));

            mediator.off(null, null, this);

            TransitionButtonComponent.__super__.dispose.call(this);
        }
    });

    return TransitionButtonComponent;
});
