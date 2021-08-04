define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oropricing/js/app/views/abstract-switcher
     * @extends oroui.app.views.base.View
     * @class oropricing.app.views.AbstractSwitcher
     */
    const AbstractSwitcher = BaseView.extend({
        options: {
            selectors: {
                fieldType: null,
                expressionType: null
            },
            errorMessage: ''
        },

        $form: null,
        field: null,
        expression: null,
        expressionInput: null,

        expressionLink: null,
        fieldLink: null,

        /**
         * @inheritdoc
         */
        constructor: function AbstractSwitcher(options) {
            AbstractSwitcher.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.visibleClass = 'visible';
            this.options = _.defaults(options || {}, this.options);
            if (!this.options.selectors.fieldType) {
                throw new Error('Option fieldType must be defined');
            }
            if (!this.options.selectors.expressionType) {
                throw new Error('Option expressionType must be defined');
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
                let visibleIdentifier;
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

        resetSubmitCounter: function() {
            AbstractSwitcher.onSubmitCounter = 0;
        },

        resetChildrenCounter: function() {
            AbstractSwitcher.childrenCounter = 0;
        },

        isVisible: function($field) {
            return $field.hasClass(this.visibleClass);
        },

        getValue: function($field) {
            let $value = null;
            if ($field.find('select').length > 0) {
                $value = $field.find('select').find('option:selected').attr('value');
            } else if ($field.find('input').length > 0) {
                $value = $field.find('input').val();
            }
            return $value;
        },

        setMouseLeaveEvent: function($expression) {
            $expression.mouseleave(() => {
                this.disposeTooltip($expression);
            });
        },

        showTooltip: function($expression) {
            $expression.tooltip({
                title: $expression.attr('placeholder') + ': ' + $expression.val(),
                trigger: 'manual'
            });
            $expression.tooltip('show');
        },

        disposeTooltip: function($expression) {
            $expression.tooltip('dispose');
        },

        isValid: function() {
            throw new Error('Abstract method isValid not implemented');
        },

        addValidationError: function() {
            throw new Error('Abstract method addValidationError not implemented');
        },

        /**
         * @inheritdoc
         */
        dispose: function(options) {
            if (this.disposed) {
                return;
            }

            delete this.options;
            delete this.field;
            delete this.$form;
            delete this.expression;
            delete this.expressionInput;
            delete this.expressionLink;
            delete this.fieldLink;

            AbstractSwitcher.__super__.dispose.call(this);
        }
    });

    return AbstractSwitcher;
});
