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
            skipMaskView: false,
            selectors: {
                couponCodeSelector: null,
                couponApplySelector: null
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
            var self = this;
            $.ajax({
                url: routing.generate(this.options.addCouponRoute),
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        mediator.execute('showFlashMessage', 'success', __('oro.promotion.coupon.messages.success'));
                        mediator.trigger('frontend:coupons:changed');
                    } else {
                        self._hideLoadingMask();
                        mediator.execute('showFlashMessage', 'error', response.message);
                    }
                }
            });
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
