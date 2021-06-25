define(function(require) {
    'use strict';

    const quantityVisibleLength = 6;
    const $ = require('jquery');
    const AbstractSwitcher = require('oropricing/js/app/views/abstract-switcher');

    /**
     * @export oropricing/js/app/views/quantity-switcher
     * @extends oroui.app.views.base.View
     * @class oropricing.app.views.QuantitySwitcher
     */
    const QuantitySwitcher = AbstractSwitcher.extend({
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
         * @inheritdoc
         */
        constructor: function QuantitySwitcher(options) {
            QuantitySwitcher.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            QuantitySwitcher.__super__.initialize.call(this, options);
            this.initLayout().done(this.initSwitcher.bind(this));
            this.$form.on('submit' + this.eventNamespace(), e => {
                this.onSubmit(e);
            });
        },

        addValidationError: function($identifier) {
            const $field = this.$el.closest('.price_rule')
                .find('div.error-block' + $identifier);

            $field.addClass(this.visibleClass).append($('<span></span>')
                .addClass('validation-failed').text(this.options.errorMessage).show());
        },

        initSwitcher: function() {
            this.fieldInput = this.field.find('input');
            this.changeQuantityField();
            this.expressionInput.mouseenter(() => {
                if (isNaN(this.expressionInput.val()) && (this.expressionInput.val().length > quantityVisibleLength)) {
                    this.showTooltip(this.expressionInput);
                } else {
                    this.disposeTooltip(this.expressionInput);
                }
            });

            this.setMouseLeaveEvent(this.expressionInput);

            this.expressionInput.change(() => {
                this.changeQuantityField();
            });

            this.fieldInput.change(() => {
                this.changeQuantityField();
            });
        },

        changeQuantityField: function() {
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

        changeVisibility: function($field1, $field2) {
            const $input1 = $field1.find('input');
            const $input2 = $field2.find('input');
            if (!!$input1.val()) {
                $input2.val($input1.val());
            }
            $field2.addClass(this.visibleClass).show();
            $input1.val('');
            $field1.removeClass(this.visibleClass).hide();
        },

        isValid: function() {
            return !!(this.getValue(this.field) || this.getValue(this.expression));
        },

        /**
         * @inheritdoc
         */
        dispose: function(options) {
            if (this.disposed) {
                return;
            }

            delete this.options;
            delete this.visibleClass;
            delete this.fieldInput;

            this.$form.off(this.eventNamespace());

            AbstractSwitcher.__super__.dispose.call(this);
        }
    });

    return QuantitySwitcher;
});
