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
            selectors: {
                fieldType: null,
                expressionType: null
            },
            errorMessage: ''
        },
        visibleClass: null,
        fieldInput: null,

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

        addValidationError: function() {
            this.field.append($('<span></span>').addClass('validation-failed').text(this.options.errorMessage));
        },

        initSwitcher: function () {
            this.fieldInput = this.field.find('input');
            this.changeQuantityField();
            this.expressionInput.mouseenter(_.bind(function() {
                if (isNaN(this.expressionInput.val()) && (this.expressionInput.val().length > quantityVisibleLength)) {
                    this.showTooltip(this.expressionInput);
                } else {
                    this.destroyTooltip(this.expressionInput);
                }
            }, this));

            this.setMouseLeaveEvent(this.expressionInput);

            this.expressionInput.change(_.bind(function() {
                this.changeQuantityField();
            }, this));

            this.fieldInput.change(_.bind(function() {
                this.changeQuantityField();
            }, this));
        },

        changeQuantityField: function () {
            if (this.field.hasClass(this.visibleClass) && (isNaN(this.fieldInput.val()))) {
                this.changeVisibility(this.field, this.expression);
            } else if (this.expression.hasClass(this.visibleClass)) {
                if (isNaN(this.expressionInput.val())) {
                    this.fieldInput.val('');
                } else {
                    this.changeVisibility(this.expression, this.field);
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

        isValid: function () {
            return !!(this.getValue(this.field) || this.getValue(this.expression));
        }
    });

    return QuantitySwitcher;
});
