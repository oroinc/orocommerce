define(function(require) {
    'use strict';
    var quantityVisibleLength = 6;
    var QuantitySwitcher;
    var _ = require('underscore');
    var $ = require('jquery');
    var AbstractSwitcher = require('oropricing/js/app/views/abstract-switcher');

    /**
     * @export oropricing/js/app/views/quantity-switcher
     * @extends oroui.app.views.base.View
     * @class oropricing.app.views.QuantitySwitcher
     */
    QuantitySwitcher = AbstractSwitcher.extend({
        options: {
            selectors: {},
            errorMessage: ''
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            QuantitySwitcher.__super__.initialize.apply(this, arguments);
            this.initLayout().done(_.bind(this.initSwitcher, this));
            this.$form.on('submit', _.bind(function(e) {
                this.onSubmit(e);
            }, this));
        },

        addValidationError: function($identifier) {
            var $field = this.$el.find('div' + $identifier);
            $field.append($('<span></span>').addClass('validation-failed').text(this.options.errorMessage));

        },

        initSwitcher: function () {
            var $field = this.$el.find('div' + this.options.selectors.fieldType).find('input');
            var $expression = this.$el.find('div' + this.options.selectors.expressionType).find('input');
            this.changeQuantityField();
            $expression.mouseenter(_.bind(function() {
                if (isNaN($expression.val()) && ($expression.val().length > quantityVisibleLength)) {
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

        changeQuantityField: function () {
            var $quantity = this.$el.find('div' + this.options.selectors.fieldType);
            var $quantityExpression = this.$el.find('div' + this.options.selectors.expressionType);

            if ($quantity.hasClass(this.visibleClass) && (isNaN($quantity.find('input').val()))) {
                this.changeVisibility($quantity, $quantityExpression);
            } else if ($quantityExpression.hasClass(this.visibleClass)) {
                if (isNaN($quantityExpression.find('input').val())) {
                    $quantity.find('input').val('');
                } else {
                    this.changeVisibility($quantityExpression, $quantity);
                }
            }
        },

        changeVisibility: function ($field1, $field2) {
            var $input1 = $field1.find('input');
            var $input2 = $field2.find('input');
            if (!!$input1.val()) {
                $input2.val($input1.val());
            }
            $field2.addClass(this.visibleClass).show();
            $input1.val('');
            $field1.removeClass(this.visibleClass).hide();
        },

        isValid: function ($selectors) {
            return !!(this.getValue($selectors.fieldType) || this.getValue($selectors.expressionType));
        }
    });

    return QuantitySwitcher;
});
