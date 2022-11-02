define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const mediator = require('oroui/js/mediator');

    const AppliedPromotionCollectionView = BaseView.extend({
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

        listen: {
            'entry-point:order:load:before mediator': 'showLoadingMask',
            'entry-point:order:load mediator': 'refreshCollectionBlock',
            'entry-point:order:load:after mediator': 'hideLoadingMask'
        },

        /**
         * @inheritdoc
         */
        constructor: function AppliedPromotionCollectionView(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();
            AppliedPromotionCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        events: function() {
            const events = {};
            events['click ' + this.options.selectors.changeActiveButton] = 'changeActiveStatus';
            events['click ' + this.options.selectors.removeButton] = 'removeAppliedPromotion';

            return events;
        },

        /**
         * @param {jQuery.Event} event
         */
        changeActiveStatus: function(event) {
            const self = this;
            const $tableRow = $(event.target).closest(this.options.selectors.appliedPromotionTableRow);
            const sourcePromotionId = $tableRow.data(this.options.sourcePromotionIdDataAttribute);

            this.$(this.options.selectors.appliedPromotionElement).each(function() {
                if ($(this).data(self.options.sourcePromotionIdDataAttribute) === sourcePromotionId) {
                    const $activeField = $(this).find(self.options.selectors.appliedPromotionActiveField);
                    const activeState = +$activeField.val();
                    const newActiveState = !activeState;
                    $activeField.val(+newActiveState);
                }
            });

            mediator.trigger('entry-point:order:trigger');
        },

        /**
         * @param {jQuery.Event} event
         */
        removeAppliedPromotion: function(event) {
            const self = this;
            const $tableRow = $(event.target).closest(this.options.selectors.appliedPromotionTableRow);
            const sourcePromotionId = $tableRow.data(this.options.sourcePromotionIdDataAttribute);
            const sourceCouponId = $tableRow.data(this.options.sourceCouponIdDataAttribute);

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
                const $content = $(response.appliedPromotions);
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
            const requiredSelectors = [];
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
