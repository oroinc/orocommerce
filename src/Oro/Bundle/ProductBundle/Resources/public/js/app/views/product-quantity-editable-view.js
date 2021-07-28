define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const ApiAccessor = require('oroui/js/tools/api-accessor');
    const mediator = require('oroui/js/mediator');
    const tools = require('oroui/js/tools');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    /** @var QuantityHelper QuantityHelper **/
    const QuantityHelper = require('oroproduct/js/app/quantity-helper');
    const ENTER_KEY_CODE = 13;

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
                this.deferredInit.done(_.bind(this.initListeners, this));
            } else {
                this.initListeners();
            }

            this._bindEvents();
            this.disableOptions();
        },

        _bindEvents: function() {
            this.elements.unit.on('change' + this.eventNamespace(), _.bind(function() {
                mediator.trigger('unitChanged');
            }, this));

            // Emulate change event on blur and press enter in MS browsers
            if (tools.isIE11() || tools.isEDGE()) {
                let valueBeforeInput = this.elements.quantity.val();

                this.elements.quantity
                    .on('focus' + this.eventNamespace(), function(e) {
                        valueBeforeInput = e.target.value;
                    })
                    .on('blur' + this.eventNamespace(), function(e) {
                        if (valueBeforeInput !== e.target.value) {
                            $(e.target).trigger('change');
                        }
                    })
                    .on('keydown' + this.eventNamespace(), function(e) {
                        if (e.keyCode === ENTER_KEY_CODE && !e.ctrlKey && valueBeforeInput !== e.target.value) {
                            $(e.target).trigger('change');
                        }
                    });
            }

            mediator.on('unitChanged', this.disableOptions, this);
        },

        disableOptions: function() {
            this.elements.unit.find('option').prop('disabled', false);

            this.$el.siblings().each(_.bind(function(index, el) {
                const value = $(el).find('[name="unit"]').val();
                this.elements.unit
                    .find('[value="' + value + '"]')
                    .prop('disabled', true);
            }, this));
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
                waitors.push(tools.loadModuleAndReplace(options.validation, 'showErrorsHandler').then(
                    _.bind(function() {
                        validationOptions.showErrors = options.validation.showErrorsHandler;
                        this.updateValidation($form, validationOptions);
                    }, this)
                ));
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
                this.elements.saveButton.on('click', _.bind(this.onViewChange, this));
                changeAction = this.enableAccept;
            }

            this.$el.on('change', this.elements.quantity, _.bind(changeAction, this));
            this.$el.on('change', this.elements.unit, _.bind(changeAction, this));
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
                .done(_.bind(this.onSaveSuccess, this))
                .fail(_.bind(this.onSaveError, this))
                .always(_.bind(function() {
                    if (!this.disposed) {
                        this._isSaving = false;
                    }
                }, this));
        },

        onSaveSuccess: function(response) {
            this.saveModelState();
            this.restoreSavedState();

            const value = _.extend({}, this.triggerData || {}, {
                value: this.getValue()
            });
            this.trigger('product:quantity-unit:update', value);
            mediator.trigger('product:quantity-unit:update', value);

            mediator.execute('showFlashMessage', 'success', this.messages.success);
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
