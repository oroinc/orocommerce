define(function(require) {
    'use strict';

    var ProductQuantityEditableView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ApiAccessor = require('oroui/js/tools/api-accessor');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');

    ProductQuantityEditableView = BaseView.extend({
        options: {
            quantityFieldName: 'quantity',
            unitFieldName: 'unit',
            dataKey: null,
            enable: false,
            save_api_accessor: {
                http_method: 'PUT'
            },
            messages: {
                success: __('oro.form.inlineEditing.successMessage'),
                processingMessage: __('oro.form.inlineEditing.saving_progress'),
                preventWindowUnload: __('oro.form.inlineEditing.inline_edits')
            },
            elements: {
                quantity: '[name$="[quantity]"]',
                unit: '[name$="[unit]"]'
            },
            validation: {
                showErrorsHandler: null,
                rules: {
                    quantity: {
                        NotBlank: {
                            message: 'orob2b.product.validation.quantity.required'
                        },
                        OpenRange: {
                            min: 0,
                            minMessage: 'orob2b.product.validation.quantity.greaterThanZero'
                        }
                    },
                    unit: {
                        NotBlank: {
                            message: 'orob2b.product.validation.unit.required'
                        }
                    }
                }
            }
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.options, options);
            if (!options.enable) {
                return;
            }
            this._isSaving = false;

            this.messages = options.messages;
            this.dataKey = options.dataKey;
            this.quantityFieldName = options.quantityFieldName;
            this.unitFieldName = options.unitFieldName;

            this.initElements(options);

            this.model.set(this.getValue());
            this.saveModelState();

            this.saveApiAccessor = new ApiAccessor(options.save_api_accessor);
        },

        initElements: function(options) {
            this.elements = {
                quantity: this.$el.find(options.elements.quantity),
                unit: this.$el.find(options.elements.unit)
            };

            this.elements.unit.prop('disabled', false);
            if (!this.elements.unit.find(':selected').is(':disabled')) {
                this.enableQuantity();
            }

            this.initValidator(options);
            this.initListeners();
        },

        enableQuantity: function() {
            this.elements.quantity.prop('disabled', false);
        },

        initValidator: function(options) {
            var validationRules = {};
            validationRules[this.elements.quantity.attr('name')] = options.validation.rules.quantity;
            validationRules[this.elements.unit.attr('name')] = options.validation.rules.unit;

            var validationOptions = {
                rules: validationRules
            };

            if (options.validation.showErrorsHandler) {
                var waitors = [];
                waitors.push(tools.loadModuleAndReplace(options.validation, 'showErrorsHandler').then(
                    _.bind(function() {
                        validationOptions.showErrors = options.validation.showErrorsHandler;
                        this.validator = this.$el.find('form').validate(validationOptions);
                    }, this)
                ));
                this.deferredInit = $.when.apply($, waitors);
            } else {
                this.validator = this.$el.find('form').validate(validationOptions);
            }
        },

        initListeners: function() {
            this.$el.on('change', this.elements.quantity, _.bind(this.onViewChange, this));
            this.$el.on('change', this.elements.unit, _.bind(this.onViewChange, this));
        },

        saveModelState: function() {
            this.oldModelState = this.model.toJSON();
        },

        restoreSavedState: function() {
            this.model.set(this.oldModelState);

            this.elements.quantity.val(this.model.get(this.quantityFieldName));
            this.elements.unit.val(this.model.get(this.unitFieldName));
        },

        onViewChange: function() {
            if (!this.isValid()) {
                return;
            }

            var value = this.getValue();
            this.enableQuantity();
            this.model.set(this.quantityFieldName, value.quantity);
            this.model.set(this.unitFieldName, value.unit);

            this.saveChanges();
        },

        getValue: function() {
            return {
                quantity: this.elements.quantity.val(),
                unit: this.elements.unit.val()
            };
        },

        isChanged: function() {
            var modelData = this.model.toJSON();
            for (var key in modelData) {
                if (modelData.hasOwnProperty(key) && this.oldModelState[key] !== modelData[key]) {
                    return true;
                }
            }

            return false;
        },

        isValid: function() {
            return this.validator.form();
        },

        requireSave: function() {
            return !this._isSaving &&
                this.isChanged() &&
                this.isValid();
        },

        saveChanges: function() {
            if (!this.requireSave()) {
                return false;
            }

            this._isSaving = true;
            var modelData = {
                'quantity': this.model.get('quantity'),
                'unit': this.model.get('unit')
            };
            var serverUpdateData = {};
            if (this.dataKey) {
                serverUpdateData[this.dataKey] = modelData;
            } else {
                serverUpdateData = modelData;
            }

            var savePromise = this.saveApiAccessor.send(modelData, serverUpdateData, {}, {
                processingMessage: this.messages.processingMessage,
                preventWindowUnload: this.messages.preventWindowUnload
            });
            savePromise
                .done(_.bind(this.onSaveSuccess, this))
                .fail(_.bind(this.onSaveError, this))
                .always(_.bind(function() {this._isSaving = false;}, this));
        },

        onSaveSuccess: function(response) {
            if (response && !this.model.disposed) {
                _.each(response, function(item, i) {
                    if (this.model.has(i)) {
                        this.model.set(i, item);
                    }
                }, this);
            }
            this.saveModelState();
            this.elements.quantity.val(this.model.get(this.quantityFieldName));
            this.elements.unit.val(this.model.get(this.unitFieldName));

            this.trigger(
                'product:quantity-unit:update',
                {
                    quantity: this.model.get(this.quantityFieldName),
                    unit: this.model.get(this.unitFieldName)
                }
            );
            mediator.execute('showFlashMessage', 'success', this.messages.success);
        },

        onSaveError: function(jqXHR) {
            var errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;

            if (!this.model.disposed) {
                this.restoreSavedState();
            }

            var errors = [];
            switch (errorCode) {
                case 400:
                    var jqXHRerrors = jqXHR.responseJSON.errors.children;
                    for (var i in jqXHRerrors) {
                        if (jqXHRerrors.hasOwnProperty(i) && jqXHRerrors[i].errors) {
                            errors.push.apply(errors, _.values(jqXHRerrors[i].errors));
                        }
                    }
                    if (!errors.length) {
                        errors.push(__('oro.ui.unexpected_error'));
                    }
                    break;
                case 403:
                    errors.push(__('You do not have permission to perform this action.'));
                    break;
                default:
                    errors.push(__('oro.ui.unexpected_error'));
            }

            _.each(errors, function(value) {
                mediator.execute('showFlashMessage', 'error', value);
            });
        }
    });

    return ProductQuantityEditableView;
});
