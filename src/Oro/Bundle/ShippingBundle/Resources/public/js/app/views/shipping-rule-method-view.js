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
            buttonSelectorAll: '.add-all-methods',
            updateFlag: null
        },

        events: {
            'click .add-all-methods, .add-method': '_createAddRequest'
        },

        $methodSelect: null,

        $allMethodsOptions: null,

        $formElements: null,

        $buttonSelector: null,

        $grid: null,

        $buttonSelectorAll: null,

        $formParent: null,

        currency: null,

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
            this.$grid = this.form.find('.shipping-methods-grid');
            this.$methodSelect = $(this.el).find(this.options.methodSelectSelector).data().inputWidget.$el;
            this.$allMethodsOptions = this.$methodSelect.find('option[value][value!=""]').clone();
            this.$buttonSelector = $(this.el).find(options.buttonSelector);
            this.$buttonSelectorAll = $(this.el).find(options.buttonSelectorAll);
            this.updateFormElements();

            this.setMethodCurrency();
            this._simpleSetInfoMethod();
            this._groupedSetInfoMethod();
            this.bindEvents();
            this.updateMethodSelector();
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
            this.form = $(this.el).closest('form');
            if (!this.form.length) {
                throw new TypeError('Form not found');
            }
        },

        /**
         * Bind events of select2 elements
         */
        bindEvents: function() {
            var self = this;

            this.bindShortMethodInfo();

            _.each(this.$formElements, function(element) {
                $(element).parent().on('content:remove', _.throttle(_.bind(this.removeHandler, this)));
            }, this);

            this.$methodSelect.on('change select2-selecting', function() {
                if (!$(this).select2('data')) {
                    self.disableAddButton();
                } else {
                    self.enableAddButton();
                }
            });
        },

        _createAddRequest: function(e) {
            this.updateFormElements();
            var data = this.form.serializeArray();
            var methodCount = this.$formElements.length - 1;

            if ($(e.target).hasClass('add-all-methods')) {
                Array.prototype.push.apply(data, this.$methodSelect.find('option[value][value!=""]').get().map(
                    function(option) {
                        methodCount++;
                        return {
                            'name': 'oro_shipping_methods_configs_rule[methodConfigs][' + methodCount + '][method]',
                            'value': option.value
                        };
                    })
                );
            } else if ($(e.target).hasClass('add-method')) {
                methodCount++;
                data.push({
                    'name': 'oro_shipping_methods_configs_rule[methodConfigs][' + methodCount + '][method]',
                    'value': this.$methodSelect.val()
                });
            }

            data.push({
                'name': this.options.updateFlag,
                'value': true
            });

            mediator.execute('submitPage', {
                url: this.form.attr('action'),
                type: this.form.attr('method'),
                data: $.param(data)
            });
        },

        removeHandler: function(element) {
            this.updateMethodSelector(element);
            this.enableMethodSelector();
            if (this.$formElements.length === 0) {
                this.$grid.remove();
            }
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

            this.$buttonSelectorAll.toggle((this.$allMethodsOptions.length - methods.length) > 1);

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
            $(this.el).children(this.options.buttonSelector).addClass('disabled');
        },

        enableAddButton: function() {
            $(this.el).children(this.options.buttonSelector).removeClass('disabled');
        },

        getMethod: function(element) {
            return $(element).find('input[data-name="field__method"]').val();
        },

        /**
         * Set currency label for each needed elements
         */
        setMethodCurrency: function() {
            var self = this;
            this.currency = this.form.find('[data-name="field__currency"] option:selected').text();
            var currencyText = ', ' + this.currency;
            var targetInput = $('.oro-shipping-rule-method-configs-collection input[type=text]');

            targetInput.parents('.control-group-number').find('label').each(function() {
                var labelText = $(this).text();

                if (labelText) {
                    $(this).find('em').before(currencyText);
                } else {
                    $(this).prepend(self.currency);
                }
            });
        },

        /**
         * Set short info about each simple method
         *
         * @param {jQuery} methodContainer
         */
        _simpleSetInfoMethod: function(methodContainer) {
            if (_.isUndefined(methodContainer)) {
                methodContainer = $('[data-method-view="simple"]');
            }

            methodContainer.each(_.bind(function(index, method) {
                var methodsInfo = $(method).find('.shipping-method-config__info');
                var inputContainers = $(method).find('.control-group-number');
                var selectValue = $(method).find('.control-group-choice option:selected').text();
                var selectLabel = $(method).find('.control-group-choice label').contents().eq(0).text();

                methodsInfo.empty();

                inputContainers.each(_.bind(function(index, input) {
                    var labelText = $(input).find('label').contents().eq(0).text();
                    var inputText = $(input).find('input').val();
                    if (inputText) {
                        $('<span>')
                            .text(labelText + ': ' + this.currency + inputText)
                            .appendTo(methodsInfo);
                    }
                }, this));

                methodsInfo.append(
                    $('<span>').text(selectLabel + ': ' +  selectValue)
                );
            }, this));
        },

        /**
         * Set short info about each grouped method
         *
         * @param {jQuery} methodContainer
         */
        _groupedSetInfoMethod: function(methodContainer) {
            var activeMethodCheckbox = $('.shipping-method-config-grid__active-checkbox:checked');
            if (_.isUndefined(methodContainer)) {
                methodContainer = activeMethodCheckbox.closest('[data-method-view]');
            }

            var methodsInfo = methodContainer.find('.shipping-method-config__info');

            if (methodContainer) {
                methodsInfo.empty();
            }

            activeMethodCheckbox.each(_.bind(function(index, checkbox) {
                var methodContainer = $(checkbox).closest('.control-group-oro_shipping_method_type_config');
                var methodLabel = methodContainer.find('.control-label.wrap label').contents().eq(0).text();
                var methodSurcharge = methodContainer.find('.shipping-method-config-grid__surcharge input').val();

                methodSurcharge = methodSurcharge ? ': ' + this.currency + methodSurcharge : '';
                $('<span>')
                    .text(methodLabel + methodSurcharge)
                    .appendTo(methodsInfo);
            }, this));
        },

        /**
         * Bind events for each target elements related with
         */
        bindShortMethodInfo: function() {
            var activeMethodCheckbox = $([
                    '.shipping-method-config-grid__active-checkbox',
                    '.shipping-method-config-grid__surcharge input',
                    '.shipping-method-config-grid__surcharge select'
                ].join(', '));

            activeMethodCheckbox.on('change', _.bind(function(e) {
                var targetMethodContainer = $(e.target).closest('[data-method-view]');
                var method = this['_' + targetMethodContainer.data('method-view') + 'SetInfoMethod'];

                if (method) {
                    method.call(this, targetMethodContainer);
                }
            }, this));
        },

        dispose: function() {
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
