define(function(require) {
    'use strict';

    var ShippingRuleMethodsView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    ShippingRuleMethodsView = BaseView.extend({

        /**
         * @param options Object
         */
        options: {
            methodSelectSelector: '.oro-shipping-rule-add-method-select .oro-select2',
            buttonSelector: '.add-method',
            updateFlag: null
        },

        $methodSelect: null,

        $allMethodsOptions: null,

        $formElements: null,

        $buttonSelector: null,

        $formParent: null,

        requiredOptions: ['methodSelectSelector', 'buttonSelector', 'updateFlag'],

        /**
         * Initialize view
         *
         * @param {object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.checkOptions();
            this.checkForm();
            this.$formParent = this.form.parents('#main');
            this.$methodSelect = $(this.el).find(this.options.methodSelectSelector).data().inputWidget.$el;
            this.$allMethodsOptions = this.$methodSelect.find('option[value][value!=""]').clone();
            this.$buttonSelector = $(this.el).find(options.buttonSelector);
            this.updateFormElements();

            if (_.isUndefined(this.$formParent.data('methodCount'))) {
                this.$formParent.data('methodCount', this.$formElements.length - 1);
            }

            this.bindEvents();
            this.updateMethodSelector();
        },

        _cleanUpDataBeforeRedirect: function() {
            this.$formParent.removeData('methodCount');
        },

        /**
         * Check required options of the component
         */
        checkOptions: function() {
            var self = this;
            var requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(self.options[option]);
            });

            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(','));
            }
        },

        /**
         * Check if form present
         */
        checkForm: function() {
            this.form = $(this.el).parents('form:first').get(0);
            if (this.form === undefined) {
                throw new TypeError('Form not found');
            }
            this.form = $(this.form);
        },

        /**
         * Bind events of select2 elements
         */
        bindEvents: function() {
            var self = this;

            this.$buttonSelector.on('click', _.bind(this.changeHandler, this));

            this.$formElements.each(function(index, element) {
                $(element).parent().on('content:remove', function(e) {
                    self.updateMethodSelector(element);
                    self.enableMethodSelector();
                });
            });

            this.$methodSelect.on('change select2-selecting', function() {
                if (!$(this).select2('data')) {
                    self.disableAddButton();
                } else {
                    self.enableAddButton();
                }
            });

            mediator.on('page:beforeChange', this._cleanUpDataBeforeRedirect, this);
        },

        /**
         * Check whenever form change and shows confirmation
         */
        changeHandler: function() {
            this.updateFormElements();
            var $form = this.form;
            var data = $form.serializeArray();
            var url = $form.attr('action');
            var value = this.$methodSelect.val();
            var methodCount = this.$formParent.data('methodCount');
            methodCount++;
            data.push({
                'name': 'oro_shipping_methods_configs_rule[methodConfigs][' + methodCount + '][method]',
                'value': value
            });
            data.push({
                'name': this.options.updateFlag,
                'value': true
            });
            mediator.execute('submitPage', {
                url: url,
                type: $form.attr('method'),
                data: $.param(data)
            });

            this.$formParent.data('methodCount', methodCount);
        },

        updateFormElements: function() {
            this.$formElements = this.form.find(
                '.oro-shipping-rule-method-configs-collection .row-oro.oro-multiselect-holder'
            );
        },

        updateMethodSelector: function(removedElement) {
            var self = this;
            var methods = [];
            this.updateFormElements();

            this.$formElements.each(function(index, element) {
                if (removedElement && self.getMethod(element) === self.getMethod(removedElement)) {
                    return;
                }
                methods.push($(element).find('input[data-name="field__method"]').val());
            });

            if (methods.length >= this.$allMethodsOptions.length) {
                this.disableMethodSelector();
                return;
            }
            if (!this.$methodSelect.val()) {
                this.disableAddButton();
            } else {
                this.enableAddButton();
            }
            this.$methodSelect.empty(); // remove old options
            this.$allMethodsOptions.each(function(i, option) {
                var value = $(option).val();
                if ($.inArray(value, methods) === -1) {
                    self.$methodSelect.append(self.createOption(value));
                }
            });

            this.$methodSelect.inputWidget('refresh');
            $(this.el).show();
        },

        /**
         * @param {String} value
         *
         * @return {jQuery}
         */
        createOption: function(value) {
            return this.$allMethodsOptions.filter('[value="' + value + '"]').clone();
        },

        disableMethodSelector: function() {
            this.$methodSelect.prop('disabled', true);
            this.disableAddButton();
        },

        enableMethodSelector: function() {
            this.$methodSelect.prop('disabled', false);
            this.enableAddButton();
        },

        disableAddButton: function() {
            $(this.el).children('.btn.add-method').addClass('disabled');
        },

        enableAddButton: function() {
            $(this.el).children('.btn.add-method').removeClass('disabled');
        },

        getMethod: function(element) {
            return $(element).find('input[data-name="field__method"]').val();
        },

        dispose: function() {

            mediator.off('page:beforeChange', this._cleanUpDataBeforeRedirect, this);

            delete this.$methodSelect;
            delete this.$allMethodsOptions;
            delete this.$formElements;
            delete this.$buttonSelector;
            delete this.$formParent;

            ShippingRuleMethodsView.__super__.dispose.apply(this, arguments);
        }
    });

    return ShippingRuleMethodsView;
});
