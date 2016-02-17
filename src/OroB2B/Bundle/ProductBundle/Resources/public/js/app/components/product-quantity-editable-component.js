/** @lends ProductQuantityEditableComponent */
define(function(require) {
    'use strict';

    var ProductQuantityEditableComponent;
    var BaseModel = require('oroui/js/app/models/base/model');
    var InlineEditableViewComponent = require('oroform/js/app/components/inline-editable-view-component');
    var ApiAccessor = require('oroui/js/tools/api-accessor');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');

    ProductQuantityEditableComponent = InlineEditableViewComponent.extend(/** @exports ProductQuantityEditableComponent.prototype */{
        options: {
            quantityFieldName: 'quantity',
            unitFieldName: 'unit',
            dataKey: null,
            enable: false,
            save_api_accessor: {
                route: 'orob2b_api_shopping_list_frontend_put_line_item',
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

            this.$el = options._sourceElement;

            this.elements = {
                quantity: options._sourceElement.find(options.elements.quantity),
                unit: options._sourceElement.find(options.elements.unit)
            };

            this.elements.quantity.prop('disabled', false);
            this.elements.unit.prop('disabled', false);

            this.model = new BaseModel(this.getValue());
            this.saveModelState();

            this.initListeners();

            this.saveApiAccessor = new ApiAccessor(options.save_api_accessor);
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
            var value = this.getValue();
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

        requireSave: function() {
            return this.isChanged() && !this._isSaving;
        },

        saveChanges: function() {
            if (!this.requireSave()) {
                return false;
            }

            this._isSaving = true;
            var modelData = this.model.toJSON();
            var serverUpdateData = {};
            if (this.dataKey) {
                serverUpdateData[this.dataKey] = modelData;
            } else {
                serverUpdateData = modelData;
            }

            var savePromise = this.saveApiAccessor.send(this.model.toJSON(), serverUpdateData, {}, {
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

    return ProductQuantityEditableComponent;
});
