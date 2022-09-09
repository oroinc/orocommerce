define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const ApiAccessor = require('oroui/js/tools/api-accessor');
    const mediator = require('oroui/js/mediator');
    const loadModules = require('oroui/js/app/services/load-modules');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    /** @var QuantityHelper QuantityHelper **/
    const QuantityHelper = require('oroproduct/js/app/quantity-helper');

    const ProductQuantityEditableView = BaseView.extend({
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
            successMessageOptions: {},
            elements: {
                saveButton: '',
                quantity: '[name$="[quantity]"]',
                unit: '[name$="[unit]"]'
            },
            validation: {
                showErrorsHandler: null,
                rules: {
                    quantity: {
                        NotBlank: {
                            message: 'oro.product.validation.quantity.required'
                        },
                        OpenRange: {
                            min: 0,
                            minMessage: 'oro.product.validation.quantity.greaterThanZero'
                        }
                    },
                    unit: {
                        NotBlank: {
                            message: 'oro.product.validation.unit.required'
                        }
                    }
                }
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function ProductQuantityEditableView(options) {
            ProductQuantityEditableView.__super__.constructor.call(this, options);
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
            this.successMessageOptions = options.successMessageOptions;
            this.dataKey = options.dataKey;
            this.quantityFieldName = options.quantityFieldName;
            this.unitFieldName = options.unitFieldName;
            this.triggerData = options.triggerData || null;
            this.initElements(options);

            this.saveModelState();

            this.saveApiAccessor = new ApiAccessor(options.save_api_accessor);
        },

        initElements: function(options) {
            this.elements = {};
            _.each(options.elements, function(selector, key) {
                this.elements[key] = selector ? this.$el.find(selector) : null;
            }, this);

            this.elements.unit.prop('disabled', false);

            if (!this.elements.unit.find(':selected').is(':disabled')) {
                this.enableQuantity();
            }

            this.initValidator(options);
            if (this.deferredInit) {
                this.deferredInit.done(this.initListeners.bind(this));
            } else {
                this.initListeners();
            }

            this._bindEvents();
            this.disableOptions();
        },

        _bindEvents: function() {
            this.elements.unit.on('change' + this.eventNamespace(), () => {
                mediator.trigger('unitChanged');
            });

            mediator.on('unitChanged', this.disableOptions, this);
        },

        disableOptions: function() {
            this.elements.unit.find('option').prop('disabled', false);

            this.$el.siblings().each((index, el) => {
                const value = $(el).find('[name="unit"]').val();
                this.elements.unit
                    .find('[value="' + value + '"]')
                    .prop('disabled', true);
            });
            this._updateQuantityPrecision();
        },

        enableAccept: function() {
            this.elements.saveButton.attr('disabled', false).inputWidget('refresh');
        },

        enableQuantity: function() {
            this.elements.quantity.attr('disabled', false).inputWidget('refresh');
        },

        initValidator: function(options) {
            const $form = this.$el.find('form');
            const validationRules = {};
            validationRules[this.elements.quantity.attr('name')] = options.validation.rules.quantity;
            validationRules[this.elements.unit.attr('name')] = options.validation.rules.unit;

            const validationOptions = {
                rules: validationRules
            };

            if (options.validation.showErrorsHandler) {
                const waitors = [];
                waitors.push(loadModules.fromObjectProp(options.validation, 'showErrorsHandler')
                    .then(() => {
                        validationOptions.showErrors = options.validation.showErrorsHandler;
                        this.updateValidation($form, validationOptions);
                    }));
                this.deferredInit = $.when(...waitors);
            } else {
                this.updateValidation($form, validationOptions);
            }
        },

        updateValidation: function($form, options) {
            this.validator = $form.validate();

            if (_.isObject(options)) {
                const settings = this.validator.settings;
                $.extend(true, settings, options);
            }
        },

        initListeners: function() {
            let changeAction = this.onViewChange;
            if (this.elements.saveButton) {
                this.elements.saveButton.on('click', this.onViewChange.bind(this));
                changeAction = this.enableAccept;
            }

            this.$el.on('change', this.elements.quantity, changeAction.bind(this));
            this.$el.on('change', this.elements.unit, changeAction.bind(this));
        },

        saveModelState: function() {
            this.oldModelState = this.getValue();
        },

        restoreSavedState: function() {
            const oldQuantity = this.oldModelState.quantity;
            const formattedQuantity = QuantityHelper.formatQuantity(oldQuantity);
            this.elements.quantity.val(formattedQuantity).change();
            this.elements.unit.val(this.oldModelState.unit).change();
        },

        onViewChange: function(e) {
            if (!this.isValid()) {
                return;
            }

            if (this.triggerData) {
                this.triggerData.event = e;
            }
            this.enableQuantity();
            this.saveChanges();
        },

        getValue: function() {
            const quantity = this.elements.quantity.val();
            const quantityNumber = QuantityHelper.getQuantityNumberOrDefaultValue(quantity);

            return {
                quantity: quantityNumber,
                unit: this.elements.unit.val()
            };
        },

        isChanged: function() {
            const modelData = this.getValue();
            for (const key in modelData) {
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

            const modelData = this.getValue();
            const localizedModelData = _.clone(modelData);
            localizedModelData.quantity = QuantityHelper.formatQuantity(localizedModelData.quantity);
            let serverUpdateData = {};

            if (this.dataKey) {
                serverUpdateData[this.dataKey] = localizedModelData;
            } else {
                serverUpdateData = localizedModelData;
            }

            const savePromise = this.saveApiAccessor.send(modelData, serverUpdateData, {}, {
                processingMessage: this.messages.processingMessage,
                preventWindowUnload: this.messages.preventWindowUnload,
                errorHandlerMessage: false
            });
            savePromise
                .done(this.onSaveSuccess.bind(this))
                .fail(this.onSaveError.bind(this))
                .always(() => {
                    if (!this.disposed) {
                        this._isSaving = false;
                    }
                });
        },

        onSaveSuccess: function(response) {
            this.saveModelState();
            this.restoreSavedState();

            const value = _.extend({}, this.triggerData || {}, {
                value: this.getValue()
            });
            this.trigger('product:quantity-unit:update', value);
            mediator.trigger('product:quantity-unit:update', value);

            mediator.execute('showFlashMessage', 'success', this.messages.success, this.successMessageOptions);
        },

        onSaveError: function(jqXHR) {
            const errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;
            const errors = 'responseJSON' in jqXHR ? jqXHR.responseJSON.errors.errors : [];

            this.restoreSavedState();
            switch (errorCode) {
                case 400:
                    const jqXHRerrors = 'responseJSON' in jqXHR ? jqXHR.responseJSON.errors.children : [];
                    for (const i in jqXHRerrors) {
                        if (jqXHRerrors.hasOwnProperty(i) && jqXHRerrors[i].errors) {
                            errors.push(..._.values(jqXHRerrors[i].errors));
                        }
                    }
                    if (!errors.length) {
                        errors.push(__('oro.ui.unexpected_error'));
                    }
                    break;
                case 403:
                    errors.push(__('oro.ui.forbidden_error'));
                    break;
                default:
                    errors.push(__('oro.ui.unexpected_error'));
            }

            _.each(errors, function(value) {
                mediator.execute('showFlashMessage', 'error', value);
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('unitChanged', this.disableOptions, this);
            this.elements.unit.off('change' + this.eventNamespace());
            this.elements.quantity.off(this.eventNamespace());

            ProductQuantityEditableView.__super__.dispose.call(this);
        },

        _updateQuantityPrecision: function() {
            const precisions = this.$el.data('unit-precisions') || {};
            const unit = this.elements.unit.val();

            if (unit in precisions) {
                const precision = precisions[unit];
                this.elements.quantity.data('precision', precision).inputWidget('refresh');
            }
        }
    });

    return ProductQuantityEditableView;
});
