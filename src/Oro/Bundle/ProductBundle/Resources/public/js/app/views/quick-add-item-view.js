define(function(require) {
    'use strict';

    var QuickAddItemView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var UnitsUtil = require('oroproduct/js/app/units-util');
    var BaseModel = require('oroui/js/app/models/base/model');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    QuickAddItemView = BaseView.extend(_.extend({}, ElementsHelper, {
        /**
         * @property {Object}
         */
        options: {
            defaultQuantity: 1,
            unitErrorText: 'oro.product.validation.unit.invalid'
        },

        elements: {
            sku: '[data-name="field__product-display-name"]',
            skuHiddenField: '[data-name="field__product-sku"]',
            quantity: '[data-name="field__product-quantity"]',
            unit: '[data-name="field__product-unit"]',
            remove: '[data-role="row-remove"]'
        },

        modelElements: {
            sku: 'sku',
            skuHiddenField: 'skuHiddenField',
            quantity: 'quantity',
            unit: 'unit'
        },

        elementsEvents: {
            quantity: ['keyup', 'onQuantityChange']
        },

        modelAttr: {
            sku: '',
            skuHiddenField: '',
            quantity: 0,
            unit: null,
            product_units: {},
            unit_placeholder: __('oro.product.frontend.quick_add.form.unit.default')
        },

        modelEvents: {
            sku: ['change', 'onSkuChange'],
            quantity: ['change', 'publishModelChanges'],
            unit: ['change', 'publishModelChanges'],
            product_units: ['change', 'setUnits']
        },

        listen: {
            'autocomplete:productFound mediator': 'updateModel',
            'autocomplete:productNotFound mediator': 'updateModelNotFound',
            'quick-add-form:rows-ready mediator': 'updateModelFromData',
            'quick-add-form:clear mediator': 'clearSku'
        },

        templates: {},

        validator: null,

        /**
         * @inheritDoc
         */
        constructor: function QuickAddItemView() {
            QuickAddItemView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            QuickAddItemView.__super__.initialize.apply(this, arguments);
            this.initModel(options);
            this.initializeElements(options);
            this.initializeRow();
        },

        initializeRow: function() {
            var currentSku = this.$elements.skuHiddenField.val();
            if (!currentSku.length) {
                this.clearModel();
                this.clearSku();
                this.setUnits();
            } else {
                this.updateModelFromData({
                    $el: this.$el,
                    item: {
                        sku: this.$elements.sku.data('value'),
                        skuHiddenField: currentSku,
                        quantity: this.$elements.quantity.val(),
                        unit: this.$elements.unit.val()
                    }
                });
            }
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }

            if (!this.model) {
                this.model = new BaseModel();
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        dispose: function() {
            delete this.templates;
            delete this.validator;
            QuickAddItemView.__super__.dispose.apply(this, arguments);
        },

        onQuantityChange: _.debounce(function(e) {
            this.model.set({
                quantity: $(e.currentTarget).val(),
                quantity_changed_manually: true
            });
            this.publishModelChanges();
        }, 500),

        onSkuChange: function() {
            if (this.model.get('sku') === '' && this.model.get('sku_changed_manually')) {
                this.clearModel();
            } else {
                this.publishModelChanges();
            }
        },

        updateModelFromData: function(data) {
            var obj = data.item;
            if (!obj || !this.checkEl(data.$el)) {
                return;
            }

            var canBeUpdated = this.canBeUpdated(data.item);
            if (this.model.get('sku') && !canBeUpdated) {
                return;
            }

            var resolvedUnitCode = this._resolveUnitCode(obj.unit);

            this.model.set({
                sku: obj.sku,
                skuHiddenField: obj.sku,
                quantity_changed_manually: true,
                quantity: canBeUpdated
                    ? parseFloat(this.model.get('quantity')) + parseFloat(obj.quantity) : obj.quantity,
                unit_deferred: resolvedUnitCode ? resolvedUnitCode : obj.unit
            });

            if (canBeUpdated) {
                mediator.trigger('quick-add-copy-paste-form:update-product', obj);
            }

            this.updateUI(true);
        },

        updateModel: function(data) {
            var obj = data.item;
            if (!obj || !this.checkEl(data.$el)) {
                return;
            }

            if (data.item.sku) {
                this.model.set({
                    sku: data.item.sku
                }, {
                    silent: true
                });

                this.model.set({
                    skuHiddenField: data.item.sku
                });
            }

            this.model.set({
                units_loaded: !_.isUndefined(data.item.units),
                quantity: data.item.quantity || this.model.get('quantity') || this.options.defaultQuantity,
                product_units: data.item.units || {}
            });
        },

        updateModelNotFound: function(data) {
            var obj = data.item;
            if (!obj || !this.checkEl(data.$el)) {
                return;
            }

            this.model.set({
                skuHiddenField: obj.sku
            });
            this.updateUI(true);
        },

        clearSku: function() {
            this.model.set({
                sku_changed_manually: true,
                sku: '',
                skuHiddenField: ''
            });
        },

        clearModel: function() {
            this.model.set({
                units_loaded: false,
                sku_changed_manually: false,
                quantity: null,
                product_units: {},
                unit: null,
                unit_deferred: null
            });
        },

        checkEl: function($el) {
            return $el !== undefined &&
                (this.$el.attr('id') === $el.attr('id') ||
                this.$el.attr('id') === $el.parents('.quick-order-add__row').attr('id'));
        },

        canBeUpdated: function(item) {
            var resolvedUnitCode = this._resolveUnitCode(item.unit);

            return this.model.get('sku') === item.sku &&
                (this.model.get('unit') === resolvedUnitCode || this.model.get('unit_deferred') === resolvedUnitCode);
        },

        setUnits: function() {
            UnitsUtil.updateSelect(this.model, this.getElement('unit'));
            if (this.model.get('unit_deferred')) {
                var unitDeferred = this.model.get('unit_deferred');
                this.model.set('unit', this._resolveUnitCode(unitDeferred));
            }
            this.updateUI();
        },

        /**
         * Gets valid unit code by unit label or code case insensitively.
         *
         * @param {String} unit
         * @returns {String|undefined}
         * @private
         */
        _resolveUnitCode: function(unit) {
            var labels = UnitsUtil.getUnitsLabel(this.model);
            unit = unit ? unit.toLowerCase() : undefined;

            // Finds valid unit code if unit is unit code.
            var unitCode = _.find(_.keys(labels), function(unitCode) {
                return unitCode.toLowerCase() === unit;
            });

            // Finds valid unit label if unit is unit label.
            var unitLabel = _.find(labels, function(unitLabel) {
                return unitLabel.toLowerCase() === unit;
            });

            // If unit label is found, gets unit code by unit label.
            if (unitLabel !== undefined) {
                unitCode = _.invert(labels)[unitLabel];
            }

            return unitCode;
        },

        publishModelChanges: function() {
            mediator.trigger('quick-add-item:model-change', {item: this.model.attributes, $el: this.$el});
            var precision = this.model.get('product_units')[this.model.get('unit')];

            this.getElement('quantity')
                .data('precision', precision)
                .inputWidget('refresh');
        },

        showUnitError: function() {
            this.getValidator().showErrors(this.getUnitError());
        },

        getValidator: function() {
            if (this.validator === null) {
                this.validator = this.$el.closest('form').validate();
            }

            return this.validator;
        },

        getUnitError: function() {
            var error = [];
            error[this.getElement('unit').attr('name')] =
                __(this.options.unitErrorText, {unit: this.model.get('unit') || this.model.get('unit_deferred')});
            return error;
        },

        unitInvalid: function() {
            return this.model.get('units_loaded') &&
                !_.has(this.model.get('product_units'), this.model.get('unit'));
        },

        updateUI: function(triggerBlur) {
            if (triggerBlur) {
                this.getElement('sku').trigger('blur');
            }

            this.getElement('unit').inputWidget('refresh');

            if (this.model.get('sku') && this.unitInvalid()) {
                this.showUnitError();
                _.defer(_.bind(function() {
                    mediator.trigger('quick-add-form-item:unit-invalid', {$el: this.$el});
                }, this));
            } else if (this.model.get('sku') && this.model.get('units_loaded')) {
                mediator.trigger('quick-add-form-item:item-valid', {item: this.model.attributes});
            }

            this.getElement('remove').toggle(this.model.get('sku') !== this.modelAttr.sku);
        }
    }));

    return QuickAddItemView;
});
