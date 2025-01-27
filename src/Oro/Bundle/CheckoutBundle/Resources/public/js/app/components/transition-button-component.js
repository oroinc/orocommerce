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
                checkoutSummary: '[data-role="checkout-summary"]',
                checkoutTotals: '[data-role="checkout-totals"]',
                checkoutTitleRequiredLabel: '[data-role="checkout-title"] [data-role="require-label"]',
                transitionTriggerContainer: '[data-role="transition-trigger-container"]',
                transitionTrigger: '[data-role="transition-trigger"]',
                stateToken: '[name$="[state_token]"]'
            },
            relatedCheckoutFormIds: []
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
                this.$form = this.getForm();
                this.$form.bindFirst('submit.' + this.cid, this.preventSubmit.bind(this));
                this.$form.on('submit.' + this.cid, this.onSubmit.bind(this));

                this.getRelativeForms().forEach(
                    form => [...form.find('[form]')].map(input => this.stashFormAttr(input))
                );
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

        getForm() {
            return this.$el.prop('form') ? $(this.$el.prop('form')) : this.$el.closest('form');
        },

        enableTransitionButton: function() {
            if (this.options.enabled) {
                this.$el.prop('disabled', false).removeClass(DISABLED_CLASS);
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

            this.$el.trigger('operation-button:init');
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

        /**
         * @param {jQuery.Event} event
         * @param {object} extraData
         *
         * @returns {boolean}
         */
        onSubmit: function(event, extraData) {
            if (event.originalEvent && !event.originalEvent.submitter.isEqualNode(this.$el[0])) {
                return false;
            }

            if (this.options.flashMessageOnSubmit) {
                event.preventDefault();
                mediator.execute('showFlashMessage', 'error', this.options.flashMessageOnSubmit);
                return false;
            }

            event.preventDefault();

            if (this.formsValidate()) {
                const eventData = {
                    stopped: false,
                    event: event,
                    extraData: (extraData || {})
                };

                mediator.trigger('checkout:before-submit', eventData);
                if (eventData.stopped) {
                    return;
                }

                this.transit(event, {method: 'POST'});
            }
        },

        stashFormAttr(input) {
            input.setAttribute('data-form', input.getAttribute('form'));
            input.removeAttribute('form');

            return input;
        },

        unStashFormAttr(input) {
            input.setAttribute('form', input.getAttribute('data-form'));
            input.removeAttribute('data-form');

            return input;
        },

        formsValidate() {
            return this.getAllForms().every(form => {
                form.validate();
                return form.valid();
            });
        },

        getAllForms() {
            return [this.$form, ...this.getRelativeForms()];
        },

        getRelativeForms() {
            const forms = [];

            if (this.options.relatedCheckoutFormIds) {
                forms.push(...this.options.relatedCheckoutFormIds.map(
                    relatedCheckoutFormId => $(`#${relatedCheckoutFormId}`)
                ).filter(form => form.length));
            }

            return forms;
        },

        /**
         * @param {Event} e
         * @param {Object} data
         */
        transit: function(e, data) {
            e.preventDefault();
            e.stopPropagation();
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
            const formData = this.getAllForms().reduce((formData, form) => {
                form.find(this.options.selectors.stateToken).prop('disabled', false);
                for (const [name, value] of new FormData(form[0]).entries()) {
                    formData.append(name, value);
                }
                return formData;
            }, new FormData());

            if (!this.$form.find(':input').not(':button, :disabled').length) {
                const formName = this.$form.attr('name') || 'oro_workflow_transition';
                formData.append(`${formName}[]`, '');
            }

            return formData;
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
                const summarySelector = this.options.selectors.checkoutSummary;
                const totalsSelector = this.options.selectors.checkoutTotals;
                const checkoutTitleRequiredLabelSelector = this.options.selectors.checkoutTitleRequiredLabel;

                mediator.trigger('checkout-content:before-update');

                $(checkoutTitleRequiredLabelSelector).remove();

                const $sidebar = $(sidebarSelector);
                $sidebar.html($response.find(sidebarSelector).html());

                const $content = $(contentSelector);
                $content.html($response.find(contentSelector).html());

                const $summary = $(summarySelector);
                $summary.html($response.find(summarySelector).html());

                const $totals = $(totalsSelector);
                $totals.html($response.find(totalsSelector).html());

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

            this.getRelativeForms().forEach(
                form => [...form.find('[form]')].map(input => this.unStashFormAttr(input))
            );

            this.$el.trigger('operation-button:dispose');
            this.$el.off('.' + this.cid);
            this.$transitionTriggers.off('.' + this.cid);

            mediator.off(null, null, this);
            TransitionButtonComponent.__super__.dispose.call(this);
        }
    });

    return TransitionButtonComponent;
});
