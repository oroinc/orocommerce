define(function(require) {
    'use strict';

    const unitAndCurrencyVisibleLength = 12;
    const $ = require('jquery');
    const AbstractSwitcher = require('oropricing/js/app/views/abstract-switcher');

    /**
     * @export oropricing/js/app/views/expression-field-switcher
     * @extends oroui.app.views.base.View
     * @class oropricing.app.views.ExpressionFieldSwitcher
     */
    const ExpressionFieldSwitcher = AbstractSwitcher.extend({
        options: {
            selectors: {
                fieldType: null,
                expressionType: null
            },
            errorMessage: ''
        },
        visibleClass: null,

        /**
         * @inheritdoc
         */
        constructor: function ExpressionFieldSwitcher(options) {
            ExpressionFieldSwitcher.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ExpressionFieldSwitcher.__super__.initialize.call(this, options);
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
            const $expressionIdentifier = this.options.selectors.expressionType;
            this.expressionLink.click(() => {
                this.changeFieldVisibility(this.field, this.expression);
                this.expressionInput.val('');
                $('div.error-block' + $expressionIdentifier).find('.validation-failed').remove();
            });

            this.fieldLink.click(() => {
                this.changeFieldVisibility(this.expression, this.field);
            });

            if (this.expressionInput.val() === '') {
                this.changeFieldVisibility(this.field, this.expression);
            } else {
                this.changeFieldVisibility(this.expression, this.field);
            }

            this.bindTooltipEvents(this.expressionInput);
        },

        bindTooltipEvents: function($expressionInput) {
            $expressionInput.mouseenter(() => {
                if ($expressionInput.val().length > unitAndCurrencyVisibleLength) {
                    this.showTooltip($expressionInput);
                }
            });

            this.setMouseLeaveEvent($expressionInput);
        },

        changeFieldVisibility: function($show, $hide) {
            $hide.removeClass(this.visibleClass).hide();
            $show.addClass(this.visibleClass).show();
        },

        isValid: function() {
            return !!(
                (
                    this.getValue(this.field) && this.field.hasClass(this.visibleClass)
                ) || (
                    this.getValue(this.expression) && this.expression.hasClass(this.visibleClass)
                )
            );
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

            this.$form.off(this.eventNamespace());

            AbstractSwitcher.__super__.dispose.call(this);
        }
    });

    return ExpressionFieldSwitcher;
});
