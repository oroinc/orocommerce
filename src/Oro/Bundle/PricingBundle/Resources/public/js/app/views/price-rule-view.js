define(function(require) {
    'use strict';
    var PriceRuleView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oropricing/js/app/views/price-rule-view
     * @extends oroui.app.views.base.View
     * @class oropricing.app.views.PriceRuleView
     */
    PriceRuleView = BaseView.extend({
        /**
         * @inheritDoc
         */
        initialize: function() {
            var selectors = {
                quantity: {
                    fieldType: '.price-rule-item-quantity-type-field',
                    expressionType: '.price-rule-item-quantity-type-expression'
                },
                productUnit: {
                    fieldType: '.price-rule-item-product-unit-type-field',
                    expressionType: '.price-rule-item-product-unit-type-expression'
                },
                currency: {
                    fieldType: '.price-rule-item-currency-type-field',
                    expressionType: '.price-rule-item-currency-type-expression'
                }
            };
            this.visibleClass = 'visible';
            this.options = $.extend(true, {
                selectors: selectors
            }, this.options);

            this.initLayout().done(_.bind(this.initTypeSwitchers, this));
        },

        initTypeSwitchers: function() {
            this.initQuantitySwitcher();
            this.initProductUnitSwitcher();
            this.initCurrencySwitcher();
        },

        initQuantitySwitcher: function () {
            var $field = this.$el.find('div' + this.options.selectors.quantity.fieldType).find('input');
            var $expression = this.$el.find('div' + this.options.selectors.quantity.expressionType).find('input');
            this.changeQuantityField();
            $expression.mouseenter(_.bind(function() {
                if (isNaN($expression.val()) && ($expression.val().length > 6)) {
                    $expression.tooltip({
                        title: $expression.attr('placeholder') + ': ' + $expression.val(),
                        trigger: 'manual'
                    });
                    $expression.tooltip('show');
                } else {
                    $expression.tooltip('destroy');
                }
            }, this));

            $expression.mouseleave(_.bind(function() {
                $expression.tooltip('destroy');
            }, this));

            $expression.change(_.bind(function() {
                this.changeQuantityField();
            }, this));

            $field.change(_.bind(function() {
                this.changeQuantityField();
            }, this));
        },

        initProductUnitSwitcher: function () {
            this.initSwitcher(
                this.options.selectors.productUnit.fieldType,
                this.options.selectors.productUnit.expressionType
            );
        },

        initCurrencySwitcher: function () {
            this.initSwitcher(
                this.options.selectors.currency.fieldType,
                this.options.selectors.currency.expressionType
            );
        },

        initSwitcher: function($fieldIdentifier, $expressionIdentifier)
        {
            var $field = this.$el.find('div' + $fieldIdentifier);
            var $expression = this.$el.find('div' + $expressionIdentifier);

            $expression.find('a' + $fieldIdentifier).click(_.bind(function() {
                this.changeFieldVisibility($field, $expression);
                $expression.find('input').val('');
                $expression.find('.validation-failed').remove();
            }, this));

            $field.find('a' + $expressionIdentifier).click(_.bind(function() {
                this.changeFieldVisibility($expression, $field);
            }, this));

            if ($expression.find('input').val() === '') {
                this.changeFieldVisibility($field, $expression);
            } else {
                this.changeFieldVisibility($expression, $field);
            }

            this.bindTooltipEvents($expression);
        },

        bindTooltipEvents: function($expression)
        {
            var expressionInput = $expression.find('input');
            expressionInput.mouseenter(_.bind(function() {
                if (expressionInput.val().length > 6) {
                    expressionInput.tooltip({
                        title: expressionInput.attr('placeholder') + ': ' + expressionInput.val(),
                        trigger: 'manual'
                    });
                    expressionInput.tooltip('show');
                }
            }, this));
            expressionInput.mouseleave(_.bind(function() {
                expressionInput.tooltip('destroy');
            }, this));
        },

        changeFieldVisibility: function($show, $hide) {
            $hide.removeClass(this.visibleClass).hide();
            $show.addClass(this.visibleClass).show();
        },

        changeQuantityField: function () {
            var self = this;
            var $quantity = this.$el.find('div' + this.options.selectors.quantity.fieldType);
            var $quantityExpression = this.$el.find('div' + this.options.selectors.quantity.expressionType);

            var changeFieldVisibility = function ($field1, $field2) {
                var $input1 = $field1.find('input');
                var $input2 = $field2.find('input');
                if (!!$input1.val()) {
                    $input2.val($input1.val());
                }
                $field2.addClass(self.visibleClass).show();
                $input1.val('');
                $field1.removeClass(self.visibleClass).hide();
            }
            if ($quantity.hasClass(this.visibleClass) && (isNaN($quantity.find('input').val()))) {
                changeFieldVisibility($quantity, $quantityExpression);
            } else if ($quantityExpression.hasClass(this.visibleClass)) {
                if (isNaN($quantityExpression.find('input').val())) {
                    $quantity.find('input').val('');
                } else {
                    changeFieldVisibility($quantityExpression, $quantity);
                }
            }
        },
    });

    return PriceRuleView;
});
