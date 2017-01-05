define(function(require) {
    'use strict';

    var PossibleShippingMethodsComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        mediator = require('oroui/js/mediator'),
        __ = require('orotranslation/js/translator'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        StandardConfirmation = require('oroui/js/standart-confirmation');

    PossibleShippingMethodsComponent = BaseComponent.extend({
        selectors: {
            toggleBtn: '#possible_shipping_methods_btn',
            possibleShippingMethodForm: '[data-content="possible_shipping_methods_form"]',
            possibleShippingMethodType: '[name$="possibleShippingMethodType"]',
            calculateShipping: '[name$="[calculateShipping]"]',
            shippingMethod: '[name$="[shippingMethod]"]',
            shippingMethodType: '[name$="[shippingMethodType]"]',
            estimatedShippingCostAmount: '[name*="[estimatedShippingCostAmount]"]',
            overriddenShippingCostAmount: '[name*="[overriddenShippingCostAmount]"]'
        },

        /**
         * @property {Object}
         */
        options: {
            events: {
                before: 'entry-point:order:load:before',
                load: 'entry-point:order:load',
                after: 'entry-point:order:load:after',
                trigger: 'entry-point:order:trigger'
            }
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.$el = this.options._sourceElement;
            this.loadingMaskView = new LoadingMaskView({container: this.$el});
            this.orderHasChanged = false;
            var self = this;
            this.getPossibleShippingMethodForm().hide();
            this.getToggleButton().on('click', function() {
                self.getCalculateShippingElement().val(true);
                mediator.trigger(self.options.events.trigger);
            });
            this.getPossibleShippingMethodForm().on(
                'change',
                this.selectors.possibleShippingMethodType,
                _.bind(this.onShippingMethodTypeChange, this
                )
            );
            this.getOverriddenShippingCostElement().on(
                'change',
                _.bind(this.onOverriddenShippingCostChange, this
                )
            );
            if ($(document).find('.selected-shipping-method').length > 0) {
                this.savedShippingMethod = $(document).find('.selected-shipping-method').text();
            }
            mediator.on(this.options.events.before, this.showLoadingMask, this);
            mediator.on(this.options.events.load, this.onOrderChange, this);
            mediator.on(this.options.events.after, this.hideLoadingMask, this);

            this.$el.closest('form').on('submit', _.bind(this.onSaveForm, this));
        },

        /**
         * Show error
         */
        onSaveForm: function (e) {
            var form = $(e.target);
            form.find(this.selectors.calculateShipping).val(true);
            form.validate();
            if (form.valid() && this.orderHasChanged) {
                this.showConfirmation(form);
                return false;
            }

            return true;
        },

        showConfirmation: function(form) {
            var self = this;

            var confirmation = new StandardConfirmation({
                title: __('oro.order.possible_shipping_methods.confirmation.title'),
                content: __('oro.order.possible_shipping_methods.confirmation.content'),
                allowOk: true,
                allowCancel: true,
                okText: __('Save'),
                cancelText: __('oro.order.continue_editing')
            });

            confirmation
                .off('ok')
                .on('ok')
                .open(function() {
                    self.orderHasChanged = false;
                    form.trigger('submit');
                });
        },

        showLoadingMask: function() {
            this.orderHasChanged = true;
            if (this.getCalculateShippingElement().val()) {
                this.loadingMaskView.show();
            }
        },

        hideLoadingMask: function() {
            if (this.loadingMaskView.isShown()) {
                this.loadingMaskView.hide();
            }
        },

        onOrderChange: function(e) {
            if (e.totals) {
                this.$totals = e.totals;
            }

            if (e.possibleShippingMethods != undefined ) {
                this.getCalculateShippingElement().val(null);
                this.getToggleButton().parent('div').hide();
                this.$data = e.possibleShippingMethods;
                this.updatePossibleShippingMethods(e.possibleShippingMethods);
                this.getPossibleShippingMethodForm().show();
                this.orderHasChanged = false;
            } else if (this.isOverriddenTrigger == true) {
                this.orderHasChanged = false;
                this.isOverriddenTrigger = false;
            } else {
                this.getPossibleShippingMethodForm().hide();
                this.getToggleButton().parent('div').show();
                this.orderHasChanged = true;
            }
        },
        
        onOverriddenShippingCostChange: function() {
            this.isOverriddenTrigger = true;
        },

        updatePossibleShippingMethods: function(methods) {
            var self = this;
            var selectedMethod = this.getShippingMethodElement().val();
            var selectedType = this.getShippingMethodTypeElement().val();
            var selectedCost = this.getEstimatedShippingCostElement().val();
            var selectedFound = false;
            var priceMatched = false;
            var len = $.map(methods, function(n, i) {
                return i;
            }).length;
            var str = '';
            if (len > 0) {
                var i = 0;
                $.each(methods, function(name, method) {
                    if ($(method.types).length > 0) {
                        str = str + '<div class="method_title">';
                        if (method.isGrouped == true) {
                            str = str + '<span>' + __(method.label) + '</span>';
                        }
                        str = str + '</div>';
                        $.each(method.types, function(key, type) {
                            if (type.price.value != null) {
                                str = str + '<div><label>';
                                var checked = '';
                                if (method.identifier === selectedMethod && type.identifier === selectedType) {
                                    checked = 'checked="checked"';
                                    selectedFound = true;
                                    if (parseFloat(selectedCost) === parseFloat(type.price.value)) {
                                        priceMatched = true;
                                    }
                                    self.updateElementsValue(selectedType, selectedMethod, type.price.value, priceMatched);
                                }
                                str = str + '<input type="radio" ' + checked + ' name="possibleShippingMethodType" value="' + type.identifier +
                                    '" data-shipping-method="' + method.identifier + '" data-shipping-price="' + type.price.value + '" data-choice="' + type.identifier + '" />';
                                str = str + '<span class="radio_button_label">' + __(type.label) + ': <strong>' + type.price.currency + ' ' + type.price.value + '</strong></span>';
                                str = str + '</label></div>';
                            }
                        });
                    }
                    i = i + 1;
                    if (len > i) {
                        str = str + '<hr>';
                    }
                });
                if (selectedFound === false) {
                    $(document).find('.selected-shipping-method').css('text-decoration', 'line-through');
                    this.setElementsValue(null, null, null);
                }
                this.getPossibleShippingMethodForm().html(str);
            } else {
                $(document).find('.selected-shipping-method').find('input').css('text-decoration', 'line-through');
                this.setElementsValue(null, null, null);
                str = '<span class="notification notification_xmd notification_alert notification-radiused mb1-md">' +
                    __('oro.order.possible_shipping_methods.no_method') +
                    '</span>';
                this.getPossibleShippingMethodForm().html(str);
            }

            this.allowUnlistedAndLockFlags();
        },

        /**
         * @param {string|null} type
         * @param {string|null} method
         * @param {number|null} cost
         */
        setElementsValue: function(type, method, cost) {
            this.getShippingMethodTypeElement().val(type);
            this.getShippingMethodElement().val(method);
            this.getEstimatedShippingCostElement().val(cost);
        },

        /**
         * @param {string|null} type
         * @param {string|null} method
         * @param {number|null} cost
         * @param {boolean} matched
         */
        updateSelectedShippingMethod: function(type, method, cost, matched) {
            if (type !== null && method != null) {
                var methodLabel = (this.$data[method].isGrouped == true) ? __(this.$data[method].label) + ', ' : '';
                var typeLabel = __(this.$data[method].types[type].label);
                var currency = this.$data[method].types[type].price.currency;
                var selectedShippingMethod = methodLabel + typeLabel + ': ' + currency + ' ' + parseFloat(cost).toFixed(2);
                var $div = $("<div>", {"class": "control-group"});
                $div.append('<label class="control-label">' + __('oro.order.shipping_method.label') + '</label>');
                $div.append('<div class="controls"><div class="control-label selected-shipping-method">' +
                    selectedShippingMethod + '</div>');

                if ($(document).find('.selected-shipping-method').length > 0) {
                    $(document).find('.previously-selected-shipping-method').closest('.control-group').remove();
                    $(document).find('.selected-shipping-method').closest('.control-group').remove();
                    if (!matched && selectedShippingMethod != this.savedShippingMethod) {
                        var $prevDiv = $("<div>", {"class": "control-group"});
                        $prevDiv.append('<label class="control-label">' + __('oro.order.previous_shipping_method.label') + '</label>');
                        $prevDiv.append('<div class="controls"><div class="control-label previously-selected-shipping-method">' +
                            this.savedShippingMethod + '</div>');

                        this.$el.closest('.responsive-cell').prepend($prevDiv);
                    }
                }
                this.$el.closest('.responsive-cell').prepend($div);
            }
        },

        allowUnlistedAndLockFlags: function()
        {
            var $shippingMethodLockedFlag = $('[name$="[shippingMethodLocked]"]');
            var $allowUnlistedShippingMethodFlag = $('[name$="[allowUnlistedShippingMethod]"]');

            if ($shippingMethodLockedFlag.length <= 0 || $allowUnlistedShippingMethodFlag.length <= 0) {
                return;
            }

            var disableFlags = $('[name$="[estimatedShippingCostAmount]"]').val() <= 0;

            $shippingMethodLockedFlag.prop('disabled', disableFlags);
            $allowUnlistedShippingMethodFlag.prop('disabled', disableFlags);
        },

        /**
         * @param {string|null} type
         * @param {string|null} method
         * @param {number|null} estimated_cost
         * @param {boolean} matched
         */
        updateElementsValue: function(type, method, estimated_cost, matched) {
            var overridden_cost = this.getOverriddenShippingCostElement().val();
            var cost = (isNaN(parseFloat(overridden_cost))) ? estimated_cost : parseFloat(overridden_cost);
            this.setElementsValue(type, method, estimated_cost);
            this.updateSelectedShippingMethod(type, method, estimated_cost, matched);
            this.updateTotals(cost);
        },

        /**
         * @param {number} cost
         */
        updateTotals: function(cost) {
            if (this.$totals && cost !== null) {
                var totals = _.clone(this.$totals);
                var newTotalAmount = 0;
                $.each(totals.subtotals, function(key, subtotal) {
                    if (subtotal.type === 'shipping_cost') {
                        totals.subtotals[key].amount = cost;
                        totals.subtotals[key].currency = totals.total.currency;
                        totals.subtotals[key].visible = true;
                        totals.subtotals[key].formattedAmount = totals.total.currency + ' ' + cost;
                    }
                    newTotalAmount = parseFloat(newTotalAmount) + parseFloat(totals.subtotals[key].amount);
                });
                totals.total.amount = newTotalAmount;
                totals.total.formattedAmount = totals.total.currency + ' ' + newTotalAmount;

                mediator.trigger('shipping-cost:updated', {'totals': totals});
            }
        },

        /**
         * @param {Event} event
         */
        onShippingMethodTypeChange: function(event) {
            var method_type = $(event.target);
            var method = method_type.data('shipping-method');
            var estimated_cost = method_type.data('shipping-price');
            this.updateElementsValue(method_type.val(), method, estimated_cost, false);
            this.allowUnlistedAndLockFlags();
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getToggleButton: function() {
            if (!this.hasOwnProperty('$toggleButton')) {
                this.$toggleButton = this.$el.find(this.selectors.toggleBtn);
            }

            return this.$toggleButton;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPossibleShippingMethodForm: function() {
            if (!this.hasOwnProperty('$possibleShippingMethodForm')) {
                this.$possibleShippingMethodForm = this.$el.find(this.selectors.possibleShippingMethodForm);
            }

            return this.$possibleShippingMethodForm;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPossibleShippingMethodType: function() {
            if (!this.hasOwnProperty('$possibleShippingMethodType')) {
                this.$possibleShippingMethodType = this.$el.find(this.selectors.possibleShippingMethodType);
            }

            return this.$possibleShippingMethodType;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodTypeElement: function() {
            if (!this.hasOwnProperty('$shippingMethodTypeElement')) {
                this.$shippingMethodTypeElement = this.$el.find(this.selectors.shippingMethodType);
            }

            return this.$shippingMethodTypeElement;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodElement: function() {
            if (!this.hasOwnProperty('$shippingMethodElement')) {
                this.$shippingMethodElement = this.$el.find(this.selectors.shippingMethod);
            }

            return this.$shippingMethodElement;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getEstimatedShippingCostElement: function() {
            if (!this.hasOwnProperty('$estimatedShippingCostElement')) {
                this.$estimatedShippingCostElement = this.$el.find(this.selectors.estimatedShippingCostAmount);
            }

            return this.$estimatedShippingCostElement;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getOverriddenShippingCostElement: function() {
            if (!this.hasOwnProperty('$overriddenShippingCostElement')) {
                this.$overriddenShippingCostElement = $(document).find(this.selectors.overriddenShippingCostAmount);
            }

            return this.$overriddenShippingCostElement;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getCalculateShippingElement: function() {
            if (!this.hasOwnProperty('$calculateShippingElement')) {
                this.$calculateShippingElement = this.$el.find(this.selectors.calculateShipping);
            }

            return this.$calculateShippingElement;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.getToggleButton().off('click');
            this.getPossibleShippingMethodType().off('change');

            mediator.off(this.options.events.before, this.showLoadingMask, this);
            mediator.off(this.options.events.load, this.onOrderChange, this);
            mediator.off(this.options.events.after, this.hideLoadingMask, this);

            PossibleShippingMethodsComponent.__super__.dispose.call(this);
        }
    });

    return PossibleShippingMethodsComponent;
});
