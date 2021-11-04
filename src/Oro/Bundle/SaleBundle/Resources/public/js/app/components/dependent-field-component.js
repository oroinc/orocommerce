define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Allows to show/hide dependent element depending on the condition which is based on the value of dependee form field.
     *
     * Dependee form field configuration:
     *  - "data-dependee-id" attribute is required and should be filled with ID of dependee so you can rely on it
     *  in "data-depend-on" of dependent element.
     *
     * Dependent element configuration:
     *  - "data-depend-on" attribute is required and should be filled with ID of dependee, which you specified in its
     * "data-dependee-id" attribute.
     *
     *  - "data-show-if" attribute is optional and should be filled with condition which represents the list of
     *  value(s) of dependee, which are required to show the dependent element. List of values must be divided with "&"
     *  (conjunction operator) or "|" (disjunction operator) - it will be evaluated according to common logic expression
     *  rules. Examples:
     *  1) data-show-if="pre_configured" - dependent element will be shown if dependee value is "pre_configured"
     *  2) data-show-if="pre_configured | allow_user" - dependent element will be shown if dependee value is
     *  "pre_configured" or "allow_user" - in case if multiple choices are allowed.
     *  3) data-show-if="pre_configured | allow_user & custom" - dependent element will be shown if dependee value is
     *  "pre_configured" or ("allow_user" and "custom") - in case if multiple choices are allowed.
     *  4) data-show-if="checked" - dependent element will be shown if dependee is checked - in case of checkbox.
     *  Use "unchecked" for the opposite condition.
     *
     *  - "data-hide-if" attribute is optional and should be filled with condition which represents the list of
     *  value(s) of dependee, which are required to hide the dependent element. This option has higher priority
     *  than showing option, so if both "data-show-if" and "data-hide-if" are true, than the dependent element will be
     *  hidden.
     */
    const DependentFieldComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                rowContainer: '.control-group'
            }
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $dependee: null,

        /**
         * @inheritdoc
         */
        constructor: function DependentFieldComponent(options) {
            DependentFieldComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;
            this.$form = this.$el.closest('form');
            this.$dependee = this.$form.find('[data-dependee-id="' + this.$el.data('dependOn') + '"]');

            this.updateDependentFields = this.updateDependentFields.bind(this);
            this.updateDependentFields();
            this.$dependee.on('change', this.updateDependentFields);

            this.$el.inputWidget('create');
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('change', this.updateDependentFields);

            DependentFieldComponent.__super__.dispose.call(this);
        },

        /**
         * @returns {Array}
         */
        getDependeeValue: function() {
            let value = [];

            if (this.$dependee.is(':checkbox')) {
                value = this.$dependee.prop('checked') ? 'checked' : 'unchecked';
            } else {
                value = this.$dependee.val();
            }

            if (!(value instanceof Array)) {
                value = [value];
            }

            return value;
        },

        /**
         * @returns {string}
         */
        getShowIf: function() {
            return this.$el.data('showIf');
        },

        /**
         * @returns {string}
         */
        getHideIf: function() {
            return this.$el.data('hideIf');
        },

        /**
         * Evaluate condition
         *
         * @returns {boolean}
         */
        evaluateCondition: function() {
            let condition = this.evaluateDisjunction(this.getShowIf());

            // Evaluate hide condition only if it is specified.
            if (this.getHideIf()) {
                condition = condition && !this.evaluateDisjunction(this.getHideIf());
            }

            return condition;
        },

        /**
         * @param {string} condition
         *
         * @returns {boolean}
         */
        evaluateConjunction: function(condition) {
            const conditionsCol = typeof condition === 'string' ? condition.split(/\s?\&\s?/) : [];
            const value = this.getDependeeValue();

            for (const a in conditionsCol) {
                if (!conditionsCol.hasOwnProperty(a)) {
                    continue;
                }

                if (value.indexOf(conditionsCol[a]) === -1) {
                    return false;
                }
            }

            return true;
        },

        /**
         * @param {string} condition
         *
         * @returns {boolean}
         */
        evaluateDisjunction: function(condition) {
            const conditionsCol = typeof condition === 'string' ? condition.split(/\s?\|\s?/) : [];
            const value = this.getDependeeValue();
            let result;

            for (const a in conditionsCol) {
                if (!conditionsCol.hasOwnProperty(a)) {
                    continue;
                }

                if (conditionsCol[a].indexOf('&') !== -1) {
                    result = this.evaluateConjunction(conditionsCol[a]);
                } else {
                    result = value.indexOf(conditionsCol[a]) !== -1;
                }

                if (result === true) {
                    return true;
                }
            }

            return false;
        },

        /**
         * Show/hide dependent field based on condition.
         */
        updateDependentFields: function() {
            if (this.evaluateCondition()) {
                this.$el.closest(this.options.selectors.rowContainer).show();
            } else {
                this.$el.closest(this.options.selectors.rowContainer).hide();
            }
        }
    });

    return DependentFieldComponent;
});
