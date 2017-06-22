define(function(require) {
    'use strict';

    var PossibleShippingMethodsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var StandardConfirmation = require('oroui/js/standart-confirmation');
    var possibleShippingMethodsTemplate = require('tpl!./../templates/possible-shipping-methods-template.html');
    var selectedShippingMethodTemplate = require('tpl!./../templates/selected-shipping-method-template.html');
    var NumberFormatter = require('orolocale/js/formatter/number');

    PossibleShippingMethodsView = BaseView.extend(_.extend({}, ElementsHelper, {
        autoRender: true,

        options: {
            events: {
                before: 'entry-point:order:load:before',
                load: 'entry-point:order:load',
                after: 'entry-point:order:load:after',
                trigger: 'entry-point:order:trigger'
            },
            possibleShippingMethodsTemplate: possibleShippingMethodsTemplate,
            selectedShippingMethodTemplate: selectedShippingMethodTemplate
        },

        elements: {
            toggleBtn: '[data-role="possible_shipping_methods_btn"]',
            possibleShippingMethodForm: '[data-content="possible_shipping_methods_form"]',
            possibleShippingMethodType: '[data-name="field__possible-shipping-method-type"]',
            calculateShipping: '[data-name="field__calculate-shipping"]',
            shippingMethod: '[data-name="field__shipping-method"]',
            shippingMethodType: '[data-name="field__shipping-method-type"]',
            estimatedShippingCostAmount: '[data-name="field__estimated-shipping-cost-amount"]',
            selectedShippingMethod: ['$document', '.selected-shipping-method'],
            overriddenShippingCostAmount: ['$document', '[name*="[overriddenShippingCostAmount]"]']
        },

        elementsEvents: {
            toggleBtn: ['click', 'onToggleBtnClick'],
            overriddenShippingCostAmount: ['change', 'onOverriddenShippingCostChange'],
            possibleShippingMethodForm: ['change', 'onShippingMethodTypeChange'],
            '$form': ['submit', 'onSaveForm']
        },

        initialize: function(options) {
            PossibleShippingMethodsView.__super__.initialize.apply(this, arguments);

            this.options = $.extend(true, {}, this.options, options || {});
            this.orderHasChanged = false;

            this.$document = $(document);
            this.$form = this.$el.closest('form');

            this.initializeElements(options);

            mediator.on(this.options.events.before, this.showLoadingMask, this);
            mediator.on(this.options.events.load, this.onOrderChange, this);
            mediator.on(this.options.events.after, this.hideLoadingMask, this);
        },

        render: function() {
            this.getElement('possibleShippingMethodForm').hide();

            if (this.getElement('selectedShippingMethod').length > 0) {
                this.savedShippingMethod = this.getElement('selectedShippingMethod').text();
            }
        },

        onToggleBtnClick: function(e) {
            this.getElement('calculateShipping').val(true);
            mediator.trigger(this.options.events.trigger);
        },

        onSaveForm: function(e) {
            this.getElement('calculateShipping').val(true);

            var $form = this.getElement('$form');
            $form.validate();
            if ($form.valid() && this.orderHasChanged && !this.getElement('overriddenShippingCostAmount').val()) {
                this.showConfirmation($form);
                return false;
            }

            return true;
        },

        showConfirmation: function(form) {
            this.removeSubview('confirmation');
            this.subview('confirmation', new StandardConfirmation({
                title: _.__('oro.order.possible_shipping_methods.confirmation.title'),
                content: _.__('oro.order.possible_shipping_methods.confirmation.content'),
                okText: _.__('Save'),
                cancelText: _.__('oro.order.continue_editing')
            }));

            this.subview('confirmation')
                .off('ok').on('ok', _.bind(function() {
                    this.orderHasChanged = false;
                    this.getElement('$form').trigger('submit');
                }, this))
                .open();
        },

        showLoadingMask: function() {
            this.orderHasChanged = true;
            if (this.getElement('calculateShipping').val()) {
                this.removeSubview('loadingMask');
                this.subview('loadingMask', new LoadingMaskView({
                    container: this.$el
                }));
                this.subview('loadingMask').show();
            }
        },

        hideLoadingMask: function() {
            this.removeSubview('loadingMask');
        },

        onOrderChange: function(e) {
            if (e.totals) {
                this.$totals = e.totals;
            }

            if (e.possibleShippingMethods !== undefined) {
                this.$data = e.possibleShippingMethods;
                this.getElement('calculateShipping').val(null);
                this.getElement('toggleBtn').parent('div').hide();
                this.updatePossibleShippingMethods(e.possibleShippingMethods);
                this.getElement('possibleShippingMethodForm').show();
                this.orderHasChanged = false;
            } else if (this.isOverriddenTrigger === true) {
                this.orderHasChanged = false;
                this.isOverriddenTrigger = false;
            } else {
                this.getElement('possibleShippingMethodForm').hide();
                this.getElement('toggleBtn').parent('div').show();
                this.orderHasChanged = true;
            }
        },

        onOverriddenShippingCostChange: function() {
            this.isOverriddenTrigger = true;
        },

        updatePossibleShippingMethods: function(methods) {
            var selectedMethod = this.getElement('shippingMethod').val();
            var selectedType = this.getElement('shippingMethodType').val();
            var selectedCost = this.getElement('estimatedShippingCostAmount').val();
            var selectedFound = false;
            var priceMatched = false;
            var str = null;
            if (_.size(methods) > 0) {
                _.each(methods, function(method) {
                    if (method.identifier !== selectedMethod) {
                        return;
                    }
                    _.each(method.types, function(type) {
                        if (type.price.value === null || type.identifier !== selectedType) {
                            return;
                        }
                        selectedFound = true;
                        if (parseFloat(selectedCost) === parseFloat(type.price.value)) {
                            priceMatched = true;
                        }
                        this.updateElementsValue(
                            selectedType,
                            selectedMethod,
                            type.price.value,
                            priceMatched
                        );
                    }, this);
                }, this);
                str = this.options.possibleShippingMethodsTemplate({
                    methods: methods,
                    selectedMethod: selectedMethod,
                    selectedType: selectedType,
                    formatter: NumberFormatter
                });
                if (!selectedFound) {
                    $(document).find('.selected-shipping-method').addClass('line-through-shipping-method');
                    this.setElementsValue(null, null, null);
                }
            } else {
                $(document).find('.selected-shipping-method').find('input').addClass('line-through-shipping-method');
                this.setElementsValue(null, null, null);
                str = _.__('oro.order.possible_shipping_methods.no_method');
            }
            this.getElement('possibleShippingMethodForm').html(str);
            this.allowUnlistedAndLockFlags();
        },

        /**
         * @param {string|null} type
         * @param {string|null} method
         * @param {number|null} cost
         */
        setElementsValue: function(type, method, cost) {
            this.getElement('shippingMethodType').val(type);
            this.getElement('shippingMethod').val(method);
            this.getElement('estimatedShippingCostAmount').val(cost);
        },

        /**
         * @param {string|null} type
         * @param {string|null} method
         * @param {number|null} cost
         * @param {boolean} matched
         */
        updateSelectedShippingMethod: function(type, method, cost, matched) {
            if (type === null || method === null) {
                return;
            }
            var methodLabel = this.$data[method].isGrouped ? _.__(this.$data[method].label) : '';
            var typeLabel = _.__(this.$data[method].types[type].label);
            var currency = this.$data[method].types[type].price.currency;
            var translation = this.$data[method].isGrouped ?
                'oro.shipping.method_type.backend.method_with_type_and_price.label'
                : 'oro.shipping.method_type.backend.method_type_and_price.label';
            var selectedShippingMethod = _.__(translation, {
                translatedMethod: methodLabel,
                translatedMethodType: _.__(typeLabel),
                price: NumberFormatter.formatCurrency(cost, currency)
            });

            var $div = $('<div>').html(this.options.selectedShippingMethodTemplate({
                shippingMethodLabel: _.__('oro.order.shipping_method.label'),
                shippingMethodClass: 'selected-shipping-method',
                selectedShippingMethod: selectedShippingMethod
            }));

            if ($(document).find('.selected-shipping-method').length > 0) {
                $(document).find('.previously-selected-shipping-method').closest('.control-group').remove();
                $(document).find('.selected-shipping-method').closest('.control-group').remove();
                if (!matched && this.savedShippingMethod && selectedShippingMethod !== this.savedShippingMethod) {
                    var $prevDiv = $('<div>').html(this.options.selectedShippingMethodTemplate({
                        shippingMethodLabel: _.__('oro.order.previous_shipping_method.label'),
                        shippingMethodClass: 'previously-selected-shipping-method',
                        selectedShippingMethod: this.savedShippingMethod
                    }));
                    this.$el.closest('.responsive-cell').prepend($prevDiv);
                }
            }
            this.$el.closest('.responsive-cell').prepend($div);
        },

        allowUnlistedAndLockFlags: function() {
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
         * @param {number|null} estimatedCost
         * @param {boolean} matched
         */
        updateElementsValue: function(type, method, estimatedCost, matched) {
            var overriddenCost = this.getElement('overriddenShippingCostAmount').val();
            var cost = parseFloat(overriddenCost);
            if (isNaN(cost)) {
                cost = estimatedCost;
            }
            this.setElementsValue(type, method, estimatedCost);
            this.updateSelectedShippingMethod(type, method, estimatedCost, matched);
            this.updateTotals(cost);
        },

        /**
         * @param {number} cost
         */
        updateTotals: function(cost) {
            if (!this.$totals || cost === null) {
                return;
            }
            var totals = _.clone(this.$totals);
            var newTotalAmount = 0;
            _.each(totals.subtotals, function(subtotal, key) {
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
        },

        /**
         * @param {Event} event
         */
        onShippingMethodTypeChange: function(event) {
            var methodType = $(event.target);
            var method = methodType.data('shipping-method');
            var estimatedCost = methodType.data('shipping-price');
            this.updateElementsValue(methodType.val(), method, estimatedCost, false);
            this.allowUnlistedAndLockFlags();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.disposeElements();

            delete this.$document;
            delete this.$form;
            delete this.options;

            mediator.off(null, null, this);

            PossibleShippingMethodsView.__super__.dispose.apply(this, arguments);
        }
    }));

    return PossibleShippingMethodsView;
});
