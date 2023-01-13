define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const BaseView = require('oroui/js/app/views/base/view');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const mediator = require('oroui/js/mediator');
    const errorsTemplate = require('tpl-loader!oropromotion/templates/field-errors.html');

    const FrontendCouponAddView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            entityClass: null,
            entityId: null,
            addCouponRoute: 'oro_promotion_frontend_add_coupon',
            removeCouponRoute: 'oro_promotion_frontend_remove_coupon',
            skipMaskView: false,
            messageNamespace: 'frontend-coupon-add-view',
            refreshOnSuccess: true,
            selectors: {
                couponCodeSelector: null,
                couponApplySelector: null,
                couponRemoveSelector: null,
                messagesContainer: null
            }
        },

        /**
         * @property {Object}
         */
        requiredOptions: [
            'entityClass',
            'entityId'
        ],

        /**
         * @inheritdoc
         */
        constructor: function FrontendCouponAddView(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();
            FrontendCouponAddView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            FrontendCouponAddView.__super__.initialize.call(this, options);

            // Removes update_checkout_state GET parameters to avoid accidental state updates when page is refreshed.
            const url = location.href.replace(/\&?update_checkout_state=1\&?/i, '');
            if (url !== location.href) {
                history.replaceState({}, document.title, url);
            }
        },

        /**
         * @inheritdoc
         */
        events: function() {
            const events = {};
            events['click ' + this.options.selectors.couponApplySelector] = 'applyCoupon';
            events['keydown ' + this.options.selectors.couponCodeSelector] = 'updateCouponState';
            events['change ' + this.options.selectors.couponCodeSelector] = 'updateCouponState';
            events['click ' + this.options.selectors.couponRemoveSelector] = 'removeCoupon';
            events['shown.bs.collapse'] = 'focusCouponField';

            return events;
        },

        /**
         @param {jQuery.Event} e
         */
        updateCouponState: function(e) {
            this.applyCouponByEnter(e);
            this._clearMessages();
        },

        focusCouponField: function() {
            this.$(this.options.selectors.couponCodeSelector).trigger('focus');
        },

        /**
         @param {jQuery.Event} e
         */
        applyCouponByEnter: function(e) {
            if (e.keyCode === 13) {
                this.applyCoupon(e);
            }
        },

        /**
         @param {jQuery.Event} e
         */
        applyCoupon: function(e) {
            e.preventDefault();

            const couponCode = this.$(this.options.selectors.couponCodeSelector).val();
            if (!couponCode) {
                return;
            }

            const data = {
                couponCode: couponCode,
                entityClass: this.options.entityClass,
                entityId: this.options.entityId
            };

            this._showLoadingMask();
            $.ajax({
                url: routing.generate(this.options.addCouponRoute),
                type: 'POST',
                data: data,
                dataType: 'json',
                success: response => {
                    if (response.success) {
                        this._showSuccess(__('oro.promotion.coupon.messages.coupon_code_applied_successfully'));
                        this._updatePageData();
                    } else {
                        this._showErrors(response.errors);
                    }
                }
            }).always(
                this._hideLoadingMask.bind(this)
            );
        },

        removeCoupon: function(e) {
            e.preventDefault();
            const $el = $(e.currentTarget);
            const appliedCouponId = $el.data('object-id');

            this._showLoadingMask();
            $.ajax({
                url: routing.generate(
                    this.options.removeCouponRoute,
                    {
                        entityClass: this.options.entityClass,
                        entityId: this.options.entityId,
                        id: appliedCouponId
                    }
                ),
                type: 'DELETE',
                dataType: 'json',
                success: () => {
                    this._showSuccess(__('oro.promotion.coupon.messages.removed'));
                    this._updatePageData();
                }
            }).always(
                this._hideLoadingMask.bind(this)
            );
        },

        /**
         * @param {string} message
         *
         * @private
         */
        _showSuccess: function(message) {
            this._clearMessages();
            const attr = {flash: true};
            if (this.options.refreshOnSuccess) {
                attr.afterReload = true;
            }
            mediator.execute('showFlashMessage', 'success', message, attr);
        },

        /**
         * @param {Array} errors
         *
         * @private
         */
        _showErrors: function(errors) {
            this._clearMessages();

            this.$(this.options.selectors.couponCodeSelector).addClass('input--error');
            this.$(this.options.selectors.messagesContainer).html(errorsTemplate({
                messages: errors.map(message => __(message))
            }));
        },

        _clearMessages: function() {
            this.$(this.options.selectors.couponCodeSelector).removeClass('input--error');
            this.$el.find(this.options.selectors.messagesContainer).html('');
        },

        _updatePageData: function() {
            if (this.options.refreshOnSuccess) {
                mediator.execute('showLoading');

                // Adds update_checkout_state GET parameter to force update checkout state after coupon is added.
                const parts = location.href.split('?');
                const query = (typeof parts[1] === 'undefined' ? '?' : parts[1] + '&') + 'update_checkout_state=1';
                mediator.execute('redirectTo', {url: parts[0] + '?' + query}, {replace: true, fullRedirect: true});
            } else {
                mediator.trigger('frontend:coupons:changed');
            }
        },

        /**
         * @private
         */
        _showLoadingMask: function() {
            if (this.options.skipMaskView) {
                return;
            }

            this._ensureLoadingMaskLoaded();

            if (!this.subview('loadingMask').isShown()) {
                this.subview('loadingMask').show();
            }
        },

        /**
         * @private
         */
        _hideLoadingMask: function() {
            this._ensureLoadingMaskLoaded();

            if (this.subview('loadingMask').isShown()) {
                this.subview('loadingMask').hide();
            }
        },

        /**
         * @private
         */
        _ensureLoadingMaskLoaded: function() {
            if (!this.subview('loadingMask')) {
                this.subview('loadingMask', new LoadingMaskView({container: this.$el}));
            }
        },

        /**
         * @private
         */
        _checkOptions: function() {
            const requiredMissed = this.requiredOptions.filter(option => {
                return _.isUndefined(this.options[option]) && !this.options[option];
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }

            const requiredSelectors = [];
            _.each(this.options.selectors, (selector, selectorName) => {
                if (!selector) {
                    requiredSelectors.push(selectorName);
                }
            });
            if (requiredSelectors.length) {
                throw new TypeError('Missing required selectors(s): ' + requiredSelectors.join(', '));
            }
        }
    });

    return FrontendCouponAddView;
});
