define(function(require) {
    'use strict';
    var unitAndCurrencyVisibleLength = 12;
    var ExpressionFieldSwitcher;
    var _ = require('underscore');
    var $ = require('jquery');
    var AbstractSwitcher = require('oropricing/js/app/views/abstract-switcher');

    /**
     * @export oropricing/js/app/views/expression-field-switcher
     * @extends oroui.app.views.base.View
     * @class oropricing.app.views.ExpressionFieldSwitcher
     */
    ExpressionFieldSwitcher = AbstractSwitcher.extend({
        options: {
            selectors: {},
            errorMessage: ''
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            ExpressionFieldSwitcher.__super__.initialize.apply(this, arguments);
            this.initLayout().done(_.bind(this.initSwitcher, this));
            this.$form.on('submit', _.bind(function(e) {
                this.onSubmit(e);
            }, this));
        },

        addValidationError: function($identifier) {
            var $field = $('div.error-block' + $identifier);
            $field.addClass(this.visibleClass).append($('<span></span>').addClass('validation-failed').text(this.options.errorMessage).show());
        },

        initSwitcher: function()
        {
            var $fieldIdentifier = this.options.selectors.fieldType;
            var $expressionIdentifier = this.options.selectors.expressionType;
            var $field = this.$el.find('div' + $fieldIdentifier);
            var $expression = this.$el.find('div' + $expressionIdentifier);
            $expression.find('a' + $fieldIdentifier).click(_.bind(function() {
                this.changeFieldVisibility($field, $expression);
                $expression.find('input').val('');
                $('div.error-block' + $expressionIdentifier).find('.validation-failed').remove();
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
                if (expressionInput.val().length > unitAndCurrencyVisibleLength) {
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

        isValid: function ($selectors) {
            return !!(
                (
                    this.getValue($selectors.fieldType) &&
                    (this.$el.find('div' + $selectors.fieldType).hasClass(this.visibleClass))
                ) || (
                    this.getValue($selectors.expressionType) &&
                    (this.$el.find('div' + $selectors.expressionType).hasClass(this.visibleClass))
                )
            );
        }
    });

    return ExpressionFieldSwitcher;
});
