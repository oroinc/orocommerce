define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var BaseView = require('oroui/js/app/views/base/view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var mediator = require('oroui/js/mediator');
    var FrontendCouponAddView;

    FrontendCouponAddView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            entityClass: null,
            entityId: null,
            addCouponRoute: 'oro_promotion_frontend_add_coupon',
            removeCouponRoute: 'oro_promotion_frontend_remove_coupon',
            skipMaskView: false,
            selectors: {
                couponCodeSelector: null,
                couponApplySelector: null,
                couponRemoveSelector: null
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
         * @inheritDoc
         */
        constructor: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();
            FrontendCouponAddView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        events: function() {
            var events = {};
            events['click ' + this.options.selectors.couponApplySelector] = 'applyCoupon';
            events['click ' + this.options.selectors.couponRemoveSelector] = 'removeCoupon';

            return events;
        },

        applyCoupon: function(e) {
            e.preventDefault();

            var couponCode = this.$(this.options.selectors.couponCodeSelector).val();
            if (!couponCode) {
                return;
            }

            var data = {
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
                success: _.bind(function(response) {
                    if (response.success) {
                        this._updatePageData();
                        this._showMessage('success', __('oro.promotion.coupon.messages.success'));
                    } else {
                        this._showMessage('error', response.message);
                    }
                }, this)
            }).always(
                _.bind(this._hideLoadingMask, this)
            );
        },

        removeCoupon: function(e) {
            e.preventDefault();
            var $el = $(e.currentTarget);
            var appliedCouponId = $el.data('object-id');

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
                success: _.bind(function() {
                    this._showMessage('success', __('oro.promotion.coupon.messages.removed'));
                    this._updatePageData();
                }, this)
            }).always(
                _.bind(this._hideLoadingMask, this)
            );
        },

        _showMessage: function(type, message) {
            mediator.execute('showFlashMessage', type, message);
        },

        _updatePageData: function() {
            // TODO change this condition in scope of BB-12228.
            if ($('[data-role="checkout-content"]').length) {
                mediator.execute('refreshPage');
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
            var requiredMissed = this.requiredOptions.filter(_.bind(function(option) {
                return _.isUndefined(this.options[option]) && !this.options[option];
            }, this));
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }

            var requiredSelectors = [];
            _.each(this.options.selectors, function(selector, selectorName) {
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
