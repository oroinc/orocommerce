define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var BaseView = require('oroui/js/app/views/base/view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var widgetManager = require('oroui/js/widget-manager');
    var CouponAddView;

    CouponAddView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            getAddedCouponsTableRoute: 'oro_promotion_get_added_coupons_table',
            delimiter: ',',
            skipMaskView: false,
            selectors: {
                couponAutocompleteSelector: null,
                couponAddButtonSelector: null,
                addedIdsSelector: null,
                addedCouponsContainerSelector: null,
                removeCouponButtonSelector: '[data-remove-coupon-id]'
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();
            CouponAddView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this._updateApplyButtonState();
        },

        /**
         * @inheritDoc
         */
        events: function() {
            var events = {};
            events['change ' + this.options.selectors.addedIdsSelector] = 'refreshAddedCouponsTable';
            events['click ' + this.options.selectors.couponAddButtonSelector] = 'addCoupon';
            events['click ' + this.options.selectors.removeCouponButtonSelector] = 'removeCoupon';

            return events;
        },

        addCoupon: function(e) {
            e.preventDefault();
            // TODO: Add validate ajax action in scope of BB-11280
            var $couponAutocomplete = this.$(this.options.selectors.couponAutocompleteSelector);
            var couponId = $couponAutocomplete.val();

            // Success behavior
            var currentState = this.$(this.options.selectors.addedIdsSelector).val()
                .split(this.options.delimiter)
                .concat([couponId]);
            currentState = _.filter(currentState, function(value) {
                return value !== '';
            });

            this._updateState(currentState);
            $couponAutocomplete.val(null).trigger('change');
        },

        removeCoupon: function(e) {
            var couponId = $(e.target).data('remove-coupon-id');

            var currentState = this.$(this.options.selectors.addedIdsSelector).val()
                .split(this.options.delimiter);
            currentState = _.filter(currentState, function(value) {
                return value !== '' && couponId !== parseInt(value);
            });

            this._updateState(currentState);
        },

        refreshAddedCouponsTable: function() {
            var $addedCouponsContainer = this.$(this.options.selectors.addedCouponsContainerSelector);
            this._showLoadingMask();
            $.ajax({
                url: routing.generate(this.options.getAddedCouponsTableRoute),
                type: 'POST',
                data: {ids: this.$(this.options.selectors.addedIdsSelector).val()},
                dataType: 'json',
                success: _.bind($addedCouponsContainer.html, $addedCouponsContainer)
            }).always(_.bind(this._hideLoadingMask, this));
        },

        /**
         * @param {Array} currentState
         * @private
         */
        _updateState: function(currentState) {
            var $addedIdsField = this.$(this.options.selectors.addedIdsSelector);
            var newVal = _.uniq(currentState.sort(), true).join(this.options.delimiter);
            if ($addedIdsField.val() !== newVal) {
                $addedIdsField.val(newVal).trigger('change');
                this._updateApplyButtonState();
            }
        },

        _updateApplyButtonState: function() {
            var $widgetContainer = this.$el.closest('[data-wid]');
            var $addedIdsField = this.$(this.options.selectors.addedIdsSelector);
            if ($widgetContainer.length) {
                var wid = $widgetContainer.data('wid');
                widgetManager.getWidgetInstance(wid, function(widget) {
                    widget.getAction('form_submit', 'adopted', function(submitAction) {
                        if ($addedIdsField.val()) {
                            $(submitAction).removeAttr('disabled');
                        } else {
                            $(submitAction).attr('disabled', 'disabled');
                        }
                    });
                });
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
            this.subview('loadingMask').show();
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

    return CouponAddView;
});
