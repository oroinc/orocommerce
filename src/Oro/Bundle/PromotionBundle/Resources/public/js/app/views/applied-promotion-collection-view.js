define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var mediator = require('oroui/js/mediator');
    var AppliedPromotionCollectionView;

    AppliedPromotionCollectionView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            sourcePromotionIdDataAttribute: 'source-promotion-id',
            sourceCouponIdDataAttribute: 'source-coupon-id',
            delimiter: ',',
            selectors: {
                appliedPromotionElement: '[data-role="applied-promotion-element"]',
                appliedPromotionTableRow: '[data-role="applied-discount-table-row"]',
                appliedPromotionActiveField: '[data-role="applied-promotion-active"]',
                changeActiveButton: '[data-role="applied-promotion-change-active-button"]',
                removeButton: '[data-role="applied-promotion-remove-button"]'
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function AppliedPromotionCollectionView(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();
            AppliedPromotionCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        events: function() {
            var events = {};
            events['click ' + this.options.selectors.changeActiveButton] = 'changeActiveStatus';
            events['click ' + this.options.selectors.removeButton] = 'removeAppliedPromotion';

            return events;
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            var handlers = {};
            handlers['entry-point:order:load:before'] = this.showLoadingMask;
            handlers['entry-point:order:load'] = this.refreshCollectionBlock;
            handlers['entry-point:order:load:after'] = this.hideLoadingMask;

            this.listenTo(mediator, handlers);
        },

        /**
         * @param {jQuery.Event} event
         */
        changeActiveStatus: function(event) {
            var self = this;
            var $tableRow = $(event.target).closest(this.options.selectors.appliedPromotionTableRow);
            var sourcePromotionId = $tableRow.data(this.options.sourcePromotionIdDataAttribute);

            this.$(this.options.selectors.appliedPromotionElement).each(function() {
                if ($(this).data(self.options.sourcePromotionIdDataAttribute) === sourcePromotionId) {
                    var $activeField = $(this).find(self.options.selectors.appliedPromotionActiveField);
                    var activeState = +$activeField.val();
                    var newActiveState = !activeState;
                    $activeField.val(+newActiveState);
                }
            });

            mediator.trigger('entry-point:order:trigger');
        },

        /**
         * @param {jQuery.Event} event
         */
        removeAppliedPromotion: function(event) {
            var self = this;
            var $tableRow = $(event.target).closest(this.options.selectors.appliedPromotionTableRow);
            var sourcePromotionId = $tableRow.data(this.options.sourcePromotionIdDataAttribute);
            var sourceCouponId = $tableRow.data(this.options.sourceCouponIdDataAttribute);

            this.$(this.options.selectors.appliedPromotionElement).each(function() {
                if ($(this).data(self.options.sourcePromotionIdDataAttribute) === sourcePromotionId) {
                    $(this).remove();
                    mediator.trigger('applied-coupon:remove', sourceCouponId);
                }
            });

            mediator.trigger('entry-point:order:trigger');
        },

        /**
         * @param {Object} response
         */
        refreshCollectionBlock: function(response) {
            if (!_.isUndefined(response.appliedPromotions)) {
                var $content = $(response.appliedPromotions);
                this.$el.html($content.html());
                this.$el.trigger('content:changed');
                this._removeLoadingMask();
            }
        },

        /**
         * @private
         */
        showLoadingMask: function() {
            this._ensureLoadingMaskLoaded();

            if (!this.subview('loadingMask').isShown()) {
                this.subview('loadingMask').show();
            }
        },

        /**
         * @private
         */
        hideLoadingMask: function() {
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
        _removeLoadingMask: function() {
            if (this.subview('loadingMask')) {
                this.removeSubview('loadingMask');
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

    return AppliedPromotionCollectionView;
});
