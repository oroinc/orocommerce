
define(function(require) {
    'use strict';

    var PossibleShippingMethodsComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        mediator = require('oroui/js/mediator'),
        __ = require('orotranslation/js/translator'),
        BaseComponent = require('oroui/js/app/components/base/component');
    
    PossibleShippingMethodsComponent = BaseComponent.extend({
        selectors: {
            toggleBtn: '#possible_shipping_methods_btn',
            possibleShippingMethodForm: '[data-content="possible_shipping_methods_form"]',
            possibleShippingMethodType: '[name$="possibleShippingMethodType"]',
            shippingMethod: '[name$="[shippingMethod]"]',
            shippingMethodType: '[name$="[shippingMethodType]"]',
            shippingCost: '[name$="[estimatedShippingCost][value]"]'
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.$data = options.data;
            var self = this;
            this.$el.find(this.selectors.possibleShippingMethodForm).hide();
            this.$el.find(this.selectors.toggleBtn).on('click', function(){
                $(this).hide();
                self.$el.find(self.selectors.possibleShippingMethodForm).show();
            });
            this.$el.find(this.selectors.possibleShippingMethodForm).on(
                'change',
                this.selectors.possibleShippingMethodType,
                _.bind(this.onShippingMethodTypeChange, this
                )
            );
            this.initShippingMethod();
            mediator.on('entry-point:order:load', this.onOrderChange, this);
        },

        onOrderChange: function(e) {
            if (_.isEqual(e.possibleShippingMethods, this.$data) !== true ) {
                this.$el.find(this.selectors.toggleBtn).show();
                this.$el.find(this.selectors.possibleShippingMethodForm).hide();
                this.$data = e.possibleShippingMethods;
                this.refreshPossibleShippingMethods(e.possibleShippingMethods);
            }
        },

        initShippingMethod: function() {
            var selectedTypeValue = this.getShippingMethodTypeElement().val();
            var selectedMethodValue = this.getShippingMethodElement().val();
            if (this.getPossibleShippingMethodType().length && selectedTypeValue && selectedMethodValue) {
                var selectedEl = this
                  .getPossibleShippingMethodType()
                  .filter('[value="' + selectedTypeValue + '"]')
                  .filter('[data-shipping-method="' + selectedMethodValue + '"]');
                selectedEl.prop('checked', 'checked');
                selectedEl.trigger('change');
            } else {
                var selectedType = this.getPossibleShippingMethodType().filter(':checked');
                if (selectedType.val()) {
                    var method = $(selectedType).data('shipping-method');
                    var cost = $(selectedType).data('shipping-price');
                    this.setElementsValue(selectedType.val(), method, cost);
                } else {
                    this.setElementsValue(null, null, null);
                }

            }
        },

        refreshPossibleShippingMethods: function(methods) {
            var len = $.map(methods, function(n, i) { return i; }).length;
            if (len > 0 ) {
                var str = '';
                var i = 0;
                $.each( methods, function( name, method ) {
                    if ($(method.types).length > 0 ) {
                    str = str + '<div class="method_title">';
                        if (method.isGrouped == true) {
                            str = str + '<span>' + __(method.label) + '</span>';
                        }
                    str = str + '</div>';
                    $.each( method.types, function( key, type ) {
                        if (type.price.value != null) {
                            str = str + '<div>';
                            if (type.identifier == this.getPossibleShippingMethodType) {
                                str = str + '<label class="checked">';
                            } else {
                                str = str + '<label>';
                            }
                            str = str + '<input type="radio" name="possibleShippingMethodType" value="' + type.identifier + 
                            '" data-shipping-method="' + method.identifier + '" data-shipping-price="' + type.price.value + '" data-choice="' + type.identifier + '" />';
                            str = str + '<span>' + __(type.label) + ': <strong>' + type.price.currency + ' ' + type.price.value + '</strong></span>';
                            str = str + '</label></div>';
                        }
                    });   
                    }
                    i = i + 1;
                    if (len > i) {
                        str = str + '<hr>';
                    }
                });
                this.$el.find(this.selectors.possibleShippingMethodForm).html(str);
            }
        },

        /**
         *
         * @param {string|null} type
         * @param {string|null} method
         * @param {float} cost
         */
        setElementsValue: function (type, method, cost) {
            this.getShippingMethodTypeElement().val(type);
            this.getShippingMethodElement().val(method);
            this.getShippingCostElement().val(cost);

            if (type !== null && method != null) {
                var methodLabel = (this.$data[method].isGrouped == true) ? __(this.$data[method].label) + ', ' : '';
                var typeLabel = __(this.$data[method].types[type].label);
                var currency = this.$data[method].types[type].price.currency;
                var $div = $("<div>", {"class": "control-group ship-options"});
                $div.append('<label class="control-label">' + __('oro.order.shipping_method.label') + '</label>');
                $div.append('<div class="controls"><input type="text" readonly value="' + methodLabel + 
                    typeLabel + ': ' + currency + ' ' + cost + '"></div>');
                $(document).find('.ship-options').remove();
                this.$el.closest('.responsive-cell').prepend($div);
            }
        },

        /**
         * @param {Event} event
         */
        onShippingMethodTypeChange: function(event) {
            var method_type = $(event.target);
            var method = method_type.data('shipping-method');
            var cost = method_type.data('shipping-price');
            this.setElementsValue(method_type.val(), method, cost);
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
                this.$shippingMethodTypeElement = $(document).find(this.selectors.shippingMethodType);
            }

            return this.$shippingMethodTypeElement;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodElement: function() {
            if (!this.hasOwnProperty('$shippingMethodElement')) {
                this.$shippingMethodElement = $(document).find(this.selectors.shippingMethod);
            }

            return this.$shippingMethodElement;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingCostElement: function() {
            if (!this.hasOwnProperty('$shippingCostElement')) {
                this.$shippingCostElement = $(document).find(this.selectors.shippingCost);
            }

            return this.$shippingCostElement;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.find(this.selectors.toggleBtn).off('click');
            this.getPossibleShippingMethodType().off('change');

            PossibleShippingMethodsComponent.__super__.dispose.call(this);
        }
    });

    return PossibleShippingMethodsComponent;
});
