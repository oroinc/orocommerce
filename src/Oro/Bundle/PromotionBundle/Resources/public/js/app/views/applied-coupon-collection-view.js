define(function(require) {
    'use strict';

    var $ = require('jquery');
    var routing = require('routing');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var AppliedCouponCollectionView;

    AppliedCouponCollectionView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            dialogWidgetAlias: null,
            getAppliedCouponsDataRoute: 'oro_promotion_get_applied_coupons_data',
            sourceCouponIdDataAttribute: 'source-coupon-id',
            selectors: {
                hiddenCollection: '[data-role="hidden-collection"]',
                appliedCouponElement: '[data-role="applied-coupon-element"]',
                couponCodeField: '[data-role="applied-coupon-code"]',
                sourcePromotionIdField: '[data-role="applied-coupon-source-promotion-id"]',
                sourceCouponIdField: '[data-role="applied-coupon-source-coupon-id"]',
                addedCouponsField: '[data-role="added-coupons-field"]'
            }
        },

        /**
         * @property {Object}
         */
        newCollectionElementData: {
            prototypeString: null,
            prototypeName: null,
            lastIndex: null
        },

        /**
         * @property {Array}
         */
        requiredOptions: [
            'dialogWidgetAlias'
        ],

        /**
         * @inheritDoc
         */
        constructor: function AppliedCouponCollectionView() {
            AppliedCouponCollectionView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();

            var handlers = {};
            handlers['applied-coupon:remove'] = this.removeAppliedCoupon;
            handlers.widget_initialize = this.attachDialogListeners;

            var $hiddenCollection = this.$(this.options.selectors.hiddenCollection);
            this.newCollectionElementData.prototypeString = $hiddenCollection.data('prototype');
            this.newCollectionElementData.prototypeName = $hiddenCollection.data('prototype-name');
            this.newCollectionElementData.lastIndex = $hiddenCollection.data('last-index');

            this.listenTo(mediator, handlers);
        },

        /**
         * @param {Integer} couponId
         */
        removeAppliedCoupon: function(couponId) {
            var self = this;
            couponId = parseInt(couponId);
            this.$(this.options.selectors.appliedCouponElement).each(function(key, element) {
                var $element = $(element);
                if (parseInt($element.data(self.options.sourceCouponIdDataAttribute)) === couponId) {
                    $element.remove();
                }
            });
        },

        /**
         * @param {oroui.widget.AbstractWidget} widget
         */
        attachDialogListeners: function(widget) {
            if (this.options.dialogWidgetAlias === widget.getAlias()) {
                var self = this;
                widget.on('contentLoad', function() {
                    widget.$el.on('submit', _.bind(self.onAddSubmit, self, widget));
                });
            }
        },

        /**
         * @param {oroui.widget.AbstractWidget} widget
         * @param {Event} event
         */
        onAddSubmit: function(widget, event) {
            event.preventDefault();
            mediator.trigger('entry-point:order:before');
            var self = this;
            var couponIds = widget.form.find(this.options.selectors.addedCouponsField).val();
            $.ajax({
                url: routing.generate(this.options.getAppliedCouponsDataRoute, {couponIds: couponIds}),
                type: 'GET',
                dataType: 'json',
                success: function(appliedCouponsData) {
                    _.each(appliedCouponsData, function(appliedCouponData) {
                        var $element = self._createNewCollectionElement(appliedCouponData);
                        self.$(self.options.selectors.hiddenCollection).append($element);
                    });

                    mediator.trigger('entry-point:order:trigger');
                }
            });

            widget.remove();
        },

        /**
         * @param {Array} appliedCouponData
         * @returns {jQuery}
         * @private
         */
        _createNewCollectionElement: function(appliedCouponData) {
            var html = this.newCollectionElementData.prototypeString.replace(
                tools.safeRegExp(this.newCollectionElementData.prototypeName, 'ig'),
                this.newCollectionElementData.lastIndex
            );
            var $element = $(html);

            $element.find(this.options.selectors.couponCodeField).val(appliedCouponData.couponCode);
            $element.find(this.options.selectors.sourcePromotionIdField).val(appliedCouponData.sourcePromotionId);
            $element.find(this.options.selectors.sourceCouponIdField).val(appliedCouponData.sourceCouponId);
            $element.data(this.options.sourceCouponIdDataAttribute, appliedCouponData.sourceCouponId);
            this.newCollectionElementData.lastIndex++;

            return $element;
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

    return AppliedCouponCollectionView;
});
