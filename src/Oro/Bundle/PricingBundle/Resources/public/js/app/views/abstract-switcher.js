define(function(require) {
    'use strict';
    var AbstractSwitcher;
    var _ = require('underscore');
    // var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oropricing/js/app/views/abstract-switcher
     * @extends oroui.app.views.base.View
     * @class oropricing.app.views.AbstractSwitcher
     */
    AbstractSwitcher = BaseView.extend({
        options: {
            selectors: {},
            errorMessage: ''
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.visibleClass = 'visible';
            this.options = _.defaults(options || {}, this.options);
            this.$form = this.$el.closest('form');
            AbstractSwitcher.isFormValid = true;
            this.resetSubmitCounter();
            AbstractSwitcher.childrenCounter = (AbstractSwitcher.childrenCounter + 1) || 1;
        },

        onSubmit: function(e) {
            AbstractSwitcher.onSubmitCounter = AbstractSwitcher.onSubmitCounter + 1;
            if (!this.isValid(this.options.selectors)) {
                AbstractSwitcher.isFormValid = false;
                var visibleIdentifier;
                if (this.isVisible(this.options.selectors.fieldType)) {
                    visibleIdentifier = this.options.selectors.fieldType;
                } else if (this.isVisible(this.options.selectors.expressionType)) {
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

        isVisible: function($identifier) {
            var $field = this.$el.find('div' + $identifier);
            return $field.hasClass(this.visibleClass);
        },

        getValue: function($identifier) {
            var $field = this.$el.find('div' + $identifier);
            var $value = null;
            if ($field.find('select').length > 0) {
                $value = $field.find('select').find('option:selected').attr('value');
            } else if ($field.find('input').length > 0) {
                $value = $field.find('input').val();
            }
            return $value;
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
