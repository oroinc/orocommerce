define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const StandardConfirmation = require('oroui/js/standart-confirmation');
    const possibleShippingMethodsTemplate = require('tpl-loader!./../templates/possible-shipping-methods-template.html');
    const selectedShippingMethodTemplate = require('tpl-loader!./../templates/selected-shipping-method-template.html');
    const noShippingMethodsAvailableTemplate = require('tpl-loader!./../templates/no-shipping-methods-available.html');
    const NumberFormatter = require('orolocale/js/formatter/number');

    const PossibleShippingMethodsView = BaseView.extend(_.extend({}, ElementsHelper, {
        autoRender: true,

        options: {
            events: {
                before: 'entry-point:order:load:before',
                load: 'entry-point:order:load',
                after: 'entry-point:order:load:after',
                trigger: 'entry-point:order:trigger'
            },
            savedShippingMethod: null,
            savedShippingMethodLabel: null,
            possibleShippingMethodsTemplate: possibleShippingMethodsTemplate,
            selectedShippingMethodTemplate: selectedShippingMethodTemplate,
            noShippingMethodsAvailableTemplate: noShippingMethodsAvailableTemplate
        },

        elements: {
            toggleBtn: '[data-role="possible_shipping_methods_btn"]',
            possibleShippingMethodForm: '[data-content="possible_shipping_methods_form"]',
            calculateShipping: '[data-name="field__calculate-shipping"]',
            shippingMethod: '[data-name="field__shipping-method"]',
            shippingMethodType: '[data-name="field__shipping-method-type"]',
            estimatedShippingCostAmount: '[data-name="field__estimated-shipping-cost-amount"]',
            overriddenShippingCostAmount: ['$document', '[name*="[overriddenShippingCostAmount]"]']
        },

        elementsEvents: {
            toggleBtn: ['click', 'onToggleBtnClick'],
            overriddenShippingCostAmount: ['change', 'onOverriddenShippingCostChange'],
            possibleShippingMethodForm: ['change', 'onShippingMethodTypeChange'],
            $form: ['submit', 'onSaveForm']
        },

        /**
         * @inheritdoc
         */
        constructor: function PossibleShippingMethodsView(options) {
            PossibleShippingMethodsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            PossibleShippingMethodsView.__super__.initialize.call(this, options);

            this.options = $.extend(true, {}, this.options, options || {});
            this.orderHasChanged = false;

            this.$form = this.$el.closest('form');
            this.$document = $(document);

            this.initializeElements(options);

            this.listenTo(mediator, {
                [`${this.options.events.before}`]: this.showLoadingMask,
                [`${this.options.events.load}`]: this.onOrderChange,
                [`${this.options.events.after}`]: this.hideLoadingMask
            });
        },

        render: function() {
            this.getElement('possibleShippingMethodForm').hide();
        },

        onToggleBtnClick: function(e) {
            this.getElement('calculateShipping').val(true);
            mediator.trigger(this.options.events.trigger);
        },

        onSaveForm: function(e) {
            this.getElement('calculateShipping').val(true);

            const $form = this.getElement('$form');
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
                .off('ok').on('ok', () => {
                    this.orderHasChanged = false;
                    this.getElement('$form').trigger('submit');
                })
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
                this.getElement('calculateShipping').val(null);
                this.getElement('toggleBtn').parent('div').hide();
                this.updatePossibleShippingMethods(e.possibleShippingMethods);
                this.getElement('possibleShippingMethodForm').show();
                this.orderHasChanged = false;
            } else if (this.recalculationIsNotRequired === true) {
                this.orderHasChanged = false;
                this.recalculationIsNotRequired = false;
            } else {
                this.getElement('possibleShippingMethodForm').hide();
                this.getElement('toggleBtn').parent('div').show();
                this.orderHasChanged = true;
            }
        },

        onOverriddenShippingCostChange: function() {
            this.recalculationIsNotRequired = true;
        },

        updatePossibleShippingMethods: function(methods) {
            let selectedMethod = this.getSelectedMethod();
            if (!selectedMethod && this.options.savedShippingMethod) {
                selectedMethod = this.options.savedShippingMethod;
            }
            let selectedFound = false;
            let str = this.options.noShippingMethodsAvailableTemplate();
            if (_.size(methods) > 0) {
                str = this.options.possibleShippingMethodsTemplate({
                    methods: methods,
                    selectedMethod: selectedMethod,
                    createMethodObject: this.createMethodObject,
                    areMethodsEqual: this.areMethodsEqual,
                    NumberFormatter: NumberFormatter
                });

                selectedFound = this.isMethodAvailable(methods, selectedMethod);
            }

            this.removeSelectedShippingMethod();
            if (!selectedFound) {
                this.setElementsValue(null);
                if (this.options.savedShippingMethod) {
                    this.renderPreviousSelectedShippingMethod();
                }
            }

            this.getElement('possibleShippingMethodForm').html(str);
        },

        getSelectedMethod: function() {
            const selectedMethod = this.getElement('shippingMethod').val();
            const selectedType = this.getElement('shippingMethodType').val();
            const selectedCost = this.getElement('estimatedShippingCostAmount').val();
            if (selectedMethod && selectedType && selectedCost) {
                return this.createMethodObject(selectedMethod, selectedType, selectedCost);
            }
            return null;
        },

        isMethodAvailable: function(methods, expectedMethod) {
            let selectedFound = false;
            if (!expectedMethod) {
                return selectedFound;
            }
            _.each(methods, function(method) {
                if (method.identifier !== expectedMethod.method) {
                    return;
                }
                _.each(method.types, function(type) {
                    if (type.price.value === null || type.identifier !== expectedMethod.type) {
                        return;
                    }
                    selectedFound = parseFloat(expectedMethod.cost) === parseFloat(type.price.value);
                }, this);
            }, this);

            return selectedFound;
        },

        /**
         * @param {object|null} method
         */
        setElementsValue: function(method) {
            if (!method) {
                method = this.createMethodObject(null, null, null);
            }
            this.getElement('shippingMethod').val(method.method);
            this.getElement('shippingMethodType').val(method.type);
            this.getElement('estimatedShippingCostAmount').val(method.cost);
        },

        removeSelectedShippingMethod: function() {
            this.$document.find('.selected-shipping-method').closest('.control-group').remove();
        },

        renderPreviousSelectedShippingMethod: function(label) {
            this.removeSelectedShippingMethod();
            const $prevDiv = $('<div>').html(this.options.selectedShippingMethodTemplate({
                shippingMethodLabel: _.__('oro.order.previous_shipping_method.label'),
                shippingMethodClass: 'selected-shipping-method',
                selectedShippingMethod: this.options.savedShippingMethodLabel
            }));
            this.$el.closest('.responsive-cell').prepend($prevDiv);
        },

        /**
         * @param {Event} event
         */
        onShippingMethodTypeChange: function(event) {
            const target = $(event.target);
            const method = this.createMethodObject(
                target.data('shipping-method'),
                target.val(),
                target.data('shipping-price')
            );

            this.setElementsValue(method);

            this.removeSelectedShippingMethod();
            if (this.options.savedShippingMethod && !this.areMethodsEqual(method, this.options.savedShippingMethod)) {
                this.renderPreviousSelectedShippingMethod();
            }

            this.updateTotals();
        },

        areMethodsEqual: function(methodA, methodB) {
            let equals = false;
            if (methodA && methodB) {
                equals = methodA.method === methodB.method;
                equals = equals && methodA.type === methodB.type;
                equals = equals && parseFloat(methodA.cost) === parseFloat(methodB.cost);
            }
            return equals;
        },

        createMethodObject: function(method, type, cost) {
            return {
                method: method,
                type: type,
                cost: cost
            };
        },

        updateTotals: function() {
            const overriddenCost = this.getElement('overriddenShippingCostAmount').val();
            const cost = parseFloat(overriddenCost);
            if (isNaN(cost)) {
                this.recalculationIsNotRequired = true;
                mediator.trigger(this.options.events.trigger);
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.disposeElements();

            delete this.$form;
            delete this.$document;
            delete this.options;

            PossibleShippingMethodsView.__super__.dispose.call(this);
        }
    }));

    return PossibleShippingMethodsView;
});
