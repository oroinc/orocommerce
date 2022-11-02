define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');
    const __ = require('orotranslation/js/translator');
    const BaseView = require('oroui/js/app/views/base/view');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const widgetManager = require('oroui/js/widget-manager');
    const errorsTemplate = require('tpl-loader!oropromotion/templates/field-errors.html');

    const CouponAddView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            entityClass: null,
            entityId: null,
            getAddedCouponsTableRoute: 'oro_promotion_get_added_coupons_table',
            validateCouponApplicabilityRoute: 'oro_promotion_validate_coupon_applicability',
            delimiter: ',',
            skipMaskView: false,
            selectors: {
                couponAutocompleteSelector: null,
                couponAddButtonSelector: null,
                addedIdsSelector: null,
                addedCouponsContainerSelector: null,
                removeCouponButtonSelector: '[data-remove-coupon-id]',
                selectCouponValidationContainerSelector: null,
                formSelector: null
            }
        },

        /**
         * @property {Object}
         */
        requiredOptions: [
            'entityClass'
        ],

        /**
         * @inheritdoc
         */
        constructor: function CouponAddView(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this._checkOptions();
            CouponAddView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function() {
            this._updateApplyButtonState();
        },

        /**
         * @inheritdoc
         */
        events: function() {
            const events = {};
            events['change ' + this.options.selectors.addedIdsSelector] = 'refreshAddedCouponsTable';
            events['change ' + this.options.selectors.couponAutocompleteSelector] = 'clearErrors';
            events['click ' + this.options.selectors.couponAddButtonSelector] = 'addCoupon';
            events['click ' + this.options.selectors.removeCouponButtonSelector] = 'removeCoupon';

            return events;
        },

        addCoupon: function(e) {
            e.preventDefault();

            const $couponAutocomplete = this.$(this.options.selectors.couponAutocompleteSelector);
            const $addedIdsField = this.$(this.options.selectors.addedIdsSelector);
            const couponId = $couponAutocomplete.val();
            if (!couponId) {
                return;
            }

            const $form = $(this.options.selectors.formSelector);
            const data = $form.find(':input[data-ftid]').serializeArray();
            _.each({
                couponId: couponId,
                addedCouponIds: $addedIdsField.val(),
                entityClass: this.options.entityClass,
                entityId: this.options.entityId
            }, function(value, key) {
                data.push({name: key, value: value});
            });

            this._showLoadingMask();
            const self = this;
            $.ajax({
                url: routing.generate(this.options.validateCouponApplicabilityRoute),
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    self.clearErrors();
                    if (response.success) {
                        let currentState = $addedIdsField.val().split(self.options.delimiter).concat([couponId]);
                        currentState = _.filter(currentState, function(value) {
                            return value !== '';
                        });

                        self._updateState(currentState);
                        $couponAutocomplete.val(null).trigger('change');
                    } else {
                        self._hideLoadingMask();
                        const errors = _.map(response.errors, function(message) {
                            return __(message);
                        });
                        self.$(self.options.selectors.selectCouponValidationContainerSelector)
                            .html(errorsTemplate({messages: errors}));
                    }
                }
            });
        },

        removeCoupon: function(e) {
            const couponId = $(e.target).data('remove-coupon-id');

            let currentState = this.$(this.options.selectors.addedIdsSelector).val()
                .split(this.options.delimiter);
            currentState = _.filter(currentState, function(value) {
                return value !== '' && couponId !== parseInt(value);
            });

            this._updateState(currentState);
        },

        refreshAddedCouponsTable: function() {
            const $addedCouponsContainer = this.$(this.options.selectors.addedCouponsContainerSelector);
            this._showLoadingMask();
            $.ajax({
                url: routing.generate(
                    this.options.getAddedCouponsTableRoute,
                    {addedCouponIds: this.$(this.options.selectors.addedIdsSelector).val()}
                ),
                type: 'GET',
                dataType: 'json',
                success: $addedCouponsContainer.html.bind($addedCouponsContainer)
            }).always(this._hideLoadingMask.bind(this));
        },

        clearErrors: function() {
            this.$(this.options.selectors.selectCouponValidationContainerSelector).html('');
        },

        /**
         * @param {Array} currentState
         * @private
         */
        _updateState: function(currentState) {
            const $addedIdsField = this.$(this.options.selectors.addedIdsSelector);
            const newVal = _.uniq(currentState.sort(), true).join(this.options.delimiter);
            if ($addedIdsField.val() !== newVal) {
                $addedIdsField.val(newVal).trigger('change');
                this._updateApplyButtonState();
            }
        },

        /**
         * @private
         */
        _updateApplyButtonState: function() {
            const $widgetContainer = this.$el.closest('[data-wid]');
            const $addedIdsField = this.$(this.options.selectors.addedIdsSelector);
            if ($widgetContainer.length) {
                const wid = $widgetContainer.data('wid');
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
