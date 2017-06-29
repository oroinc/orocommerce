define(function(require) {
    'use strict';

    var QuotePossibleShippingMethodsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var PossibleShippingMethodsComponent = require('oroorder/js/app/components/possible-shipping-methods-component');

    QuotePossibleShippingMethodsComponent = PossibleShippingMethodsComponent.extend({
        /**
         * @property {String}
         */
        templateSelector: '#possible-shipping-methods',

        /**
         * @property {String}
         */
        savedShippingMethod: null,

        /**
         * @property {Function}
         */
        shippingMethodsTemplate: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            QuotePossibleShippingMethodsComponent.__super__.initialize.apply(this, arguments);

            this.savedShippingMethod = options.savedShippingMethod;
            this.shippingMethodsTemplate = _.template($(this.templateSelector).html());
        },

        updatePossibleShippingMethods: function(methods) {
            var self = this;
            var selectedMethod = this.getShippingMethodElement().val();
            var selectedType = this.getShippingMethodTypeElement().val();
            var selectedCost = this.getEstimatedShippingCostElement().val();
            var selectedFound = false;
            var priceMatched = false;
            var str = '';
            if (_.keys(methods).length > 0) {
                if (this.shippingMethodsTemplate) {
                    str = this.shippingMethodsTemplate({
                        methods: methods,
                        selectedMethod: selectedMethod,
                        selectedType: selectedType,
                        selectedCost: selectedCost,
                        NumberFormatter: NumberFormatter
                    });
                }
                _.each(methods, function(method) {
                    if (!_.keys(method.types).length) {
                        return;
                    }
                    _.each(method.types, function(type) {
                        if (type.price.value === null) {
                            return;
                        }
                        if (method.identifier === selectedMethod && type.identifier === selectedType) {
                            selectedFound = true;
                            priceMatched = parseFloat(selectedCost) === parseFloat(type.price.value);
                            self.updateElementsValue(
                                selectedType,
                                selectedMethod,
                                type.price.value,
                                priceMatched
                            );
                        }
                    });
                });
                if (!selectedFound) {
                    this.setElementsValue(null, null, null);
                }
            } else {
                this.setElementsValue(null, null, null);
                str = '<span class="alert alert-error">' +
                    __('oro.order.possible_shipping_methods.no_method') + '</span>';
            }
            this.getPossibleShippingMethodForm().html(str);
            this.allowUnlistedAndLockFlags();
        },

        /**
         * @inheritDoc
         */
        updateSelectedShippingMethod: function(type, method, cost, matched) {
            if (type === null && method === null) {
                return;
            }

            if (this.savedShippingMethod !== null) {
                $(document).find('.previously-selected-shipping-method').closest('.control-group').remove();
                if (!matched && this.savedShippingMethod &&
                    this.getSelectedShippingMethod(type, method, cost) !== this.savedShippingMethod) {
                    this.renderPreviousSelectedShippingMethod();
                }
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.savedShippingMethod;
            delete this.shippingMethodsTemplate;

            PossibleShippingMethodsComponent.__super__.dispose.call(this);
        }
    });

    return QuotePossibleShippingMethodsComponent;
});
