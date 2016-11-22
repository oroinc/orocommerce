define(function(require) {
    'use strict';
    var AbstractSwitcher;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oropricing/js/app/views/abstract-switcher
     * @extends oroui.app.views.base.View
     * @class oropricing.app.views.AbstractSwitcher
     */
    AbstractSwitcher = BaseView.extend({
        options: {
            selectors: {
                fieldType: null,
                expressionType: null
            },
            errorMessage: ''
        },

        field: null,
        expression: null,
        expressionInput: null,

        expressionLink: null,
        fieldLink:null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.visibleClass = 'visible';
            this.options = _.defaults(options || {}, this.options);
            if (!this.options.selectors.fieldType) {
                throw "Option fieldType must be defined";
            }
            if (!this.options.selectors.expressionType) {
                throw "Option expressionType must be defined";
            }

            this.field = this.$el.find('div' + this.options.selectors.fieldType);
            this.expression = this.$el.find('div' + this.options.selectors.expressionType);
            this.expressionInput = this.expression.find('input');

            this.expressionLink = this.expression.find('a' + this.options.selectors.fieldType);
            this.fieldLink = this.field.find('a' + this.options.selectors.expressionType);

            this.$form = this.$el.closest('form');
            AbstractSwitcher.isFormValid = true;
            this.resetSubmitCounter();
            AbstractSwitcher.childrenCounter = (AbstractSwitcher.childrenCounter + 1) || 1;
        },

        onSubmit: function(e) {
            AbstractSwitcher.onSubmitCounter += 1;
            if (!this.isValid()) {
                AbstractSwitcher.isFormValid = false;
                var visibleIdentifier;
                if (this.isVisible(this.field)) {
                    visibleIdentifier = this.options.selectors.fieldType;
                } else if (this.isVisible(this.expression)) {
                    visibleIdentifier = this.options.selectors.expressionType;
                }
                this.addValidationError(visibleIdentifier);
            }

            if (AbstractSwitcher.onSubmitCounter === AbstractSwitcher.childrenCounter) {
                if (AbstractSwitcher.isFormValid) {
                    this.resetChildrenCounter();
                } else {
                    e.preventDefault();
                    AbstractSwitcher.isFormValid = true;
                    this.resetSubmitCounter();
                }
            }
        },

        resetSubmitCounter: function () {
            AbstractSwitcher.onSubmitCounter = 0;
        },

        resetChildrenCounter: function () {
            AbstractSwitcher.childrenCounter = 0;
        },

        isVisible: function($field) {
            return $field.hasClass(this.visibleClass);
        },

        getValue: function($field) {
            var $value = null;
            if ($field.find('select').length > 0) {
                $value = $field.find('select').find('option:selected').attr('value');
            } else if ($field.find('input').length > 0) {
                $value = $field.find('input').val();
            }
            return $value;
        },

        setMouseLeaveEvent: function ($expression) {
            $expression.mouseleave(_.bind(function() {
                this.destroyTooltip($expression);
            }, this));
        },

        showTooltip: function($expression) {
            $expression.tooltip({
                title: $expression.attr('placeholder') + ': ' + $expression.val(),
                trigger: 'manual'
            });
            $expression.tooltip('show');
        },

        destroyTooltip: function($expression) {
            $expression.tooltip('destroy');
        },

        isValid: function() {
            throw "Abstract method isValid not implemented";
        },

        addValidationError: function() {
            throw "Abstract method addValidationError not implemented";
        }
    });

    return AbstractSwitcher;
});
