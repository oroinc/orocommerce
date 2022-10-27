define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const mediator = require('oroui/js/mediator');
    const $ = require('jquery');
    const _ = require('underscore');
    const DISABLED_CLASS = 'btn--disabled';

    const TransitionButtonComponent = BaseComponent.extend(/** @lends TransitionButtonComponent.prototype */{
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
         * @inheritdoc
         */
        constructor: function TransitionButtonComponent(options) {
            TransitionButtonComponent.__super__.constructor.call(this, options);
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
                this.$form.bindFirst('submit.' + this.cid, this.preventSubmit.bind(this));
                this.$form.on('submit.' + this.cid, this.onSubmit.bind(this));
            } else {
                this.$el.on('click.' + this.cid, this.transit.bind(this));
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
            this.$transitionTriggers.on('click.' + this.cid, this.transit.bind(this));
        },

        /**
         * Prevent submit form by unexpected controls like button without attribute "type"
         * @param e
         */
        preventSubmit: function(e) {
            if (
                $(document.activeElement).is('button') &&
                $.contains(this.$form[0], document.activeElement) &&
                $(document.activeElement).attr('type') !== 'submit'
            ) {
                e.stopImmediatePropagation();
                return false;
            }
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
                .done(this.onSuccess.bind(this))
                .fail(this.onFail.bind(this));
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
                data.data = this.getFormData();
            }

            data.contentType = false;
            data.processData = false;

            return data;
        },

        /**
         * @returns FormData
         */
        getFormData: function() {
            this.$form.find(this.options.selectors.stateToken)
                .prop('disabled', false)
                .removeAttr('disabled');
            return new FormData(this.$form[0]);
        },

        onSuccess: function(response) {
            this.inProgress = false;

            if (response.hasOwnProperty('responseData')) {
                const eventData = {stopped: false, responseData: response.responseData};
                // FIXME: Inconsistent event name. This is not place-order logic, just "Continue"
                mediator.trigger('checkout:place-order:response', eventData);
                if (eventData.stopped) {
                    return;
                }
            }

            if (response.hasOwnProperty('redirectUrl')) {
                mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
            } else {
                const $response = $('<div/>').html(response);
                const $title = $response.find('title');
                if ($title.length) {
                    document.title = $title.text();
                }
                const flashNotificationsSelector = this.options.selectors.checkoutFlashNotifications;
                const sidebarSelector = this.options.selectors.checkoutSidebar;
                const contentSelector = this.options.selectors.checkoutContent;

                mediator.trigger('checkout-content:before-update');

                const $sidebar = $(sidebarSelector);
                $sidebar.html($response.find(sidebarSelector).html());

                const $content = $(contentSelector);
                $content.html($response.find(contentSelector).html());

                const $flashNotifications = $response.find(flashNotificationsSelector);

                _.each($flashNotifications, function(element) {
                    const $element = $(element);
                    const type = $element.data('type');
                    let message = $element.data('message');
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

        disposeTooltip: function() {
            this.$el.tooltip('dispose');
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.disposeTooltip();

            if (this.$form) {
                this.$form.off('.' + this.cid);
            }
            this.$el.off('.' + this.cid);
            this.$transitionTriggers.off('.' + this.cid);

            mediator.off(null, null, this);
            TransitionButtonComponent.__super__.dispose.call(this);
        }
    });

    return TransitionButtonComponent;
});
