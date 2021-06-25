define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const BaseModel = require('oroui/js/app/models/base/model');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const $ = require('jquery');
    const _ = require('underscore');
    /** @var QuantityHelper QuantityHelper **/
    const QuantityHelper = require('oroproduct/js/app/quantity-helper');

    const BaseProductView = BaseView.extend(_.extend({}, ElementsHelper, {
        optionNames: BaseView.prototype.optionNames.concat(['normalizeQuantityField']),

        normalizeQuantityField: true,

        elements: {
            productItem: '[data-role="product-item"]',
            quantity: ['lineItem', '[data-name="field__quantity"]'],
            unit: ['lineItem', '[data-name="field__unit"]:first'],
            lineItem: '[data-role="line-item-form-container"]:first',
            lineItemFields: ':input[data-name]'
        },

        elementsEvents: {
            quantity: ['change', 'onQuantityChange']
        },

        modelElements: {
            quantity: 'quantity',
            unit: 'unit'
        },

        modelAttr: {
            id: 0,
            quantity: 0,
            unit: '',
            product_units: {},
            line_item_form_enable: true
        },

        modelEvents: {
            id: ['change', 'onProductChanged'],
            line_item_form_enable: ['change', 'onLineItemFormEnableChanged'],
            unit_label: ['change', 'changeUnitLabel'],
            unit: ['change', 'onUnitChange'],
            product_units: ['change', 'onProductUnitsChange']
        },

        originalProductId: null,

        /**
         * @inheritdoc
         */
        constructor: function BaseProductView(options) {
            BaseProductView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            BaseProductView.__super__.initialize.call(this, options);

            this.rowId = this.$el.parent().data('row-id');
            this.initModel(options);
            this.initializeElements(options);
            this.getElement('quantity').inputWidget('create');
            this.setPrecision();
            // Reinitialize quantity value in model after quantity field widget refreshed to set correct value.
            this.onQuantityChange(null);

            this.originalProductId = this.model.get('parentProduct');

            this.initializeSubviews({
                productModel: this.model,
                options: {
                    productModel: this.model
                }
            });
        },

        /**
         * @inheritdoc
         */
        delegateEvents: function(events) {
            BaseProductView.__super__.delegateEvents.call(this, events);

            this.$el.one(
                'change' + this.eventNamespace(),
                function() {
                    this.$el.removeAttr('data-validation-ignore');
                }.bind(this)
            );

            return this;
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (!this.model) {
                this.model = _.isObject(this.collection) && this.collection.get(this.rowId)
                    ? this.collection.get(this.rowId) : new BaseModel();
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        onProductChanged: function() {
            const modelProductId = this.model.get('id');
            this.model.set('line_item_form_enable', Boolean(modelProductId));

            const productId = modelProductId || this.originalProductId;
            mediator.trigger('layout-subtree:update:product', {
                layoutSubtreeUrl: routing.generate('oro_product_frontend_product_view', {
                    id: productId,
                    parentProductId: this.model.get('parentProduct'),
                    ignoreProductVariant: true
                }),
                layoutSubtreeCallback: this.afterProductChanged.bind(this)
            });
        },

        viewToModelElementValueTransform: function(elementViewValue, elementKey) {
            switch (elementKey) {
                case 'quantity':
                    const $element = this.getElement(elementKey);
                    if ($element.attr('type').toLowerCase() === 'number') {
                        return parseFloat(elementViewValue);
                    }

                    return QuantityHelper.getQuantityNumberOrDefaultValue(elementViewValue, NaN);
                default:
                    return elementViewValue;
            }
        },

        modelToViewElementValueTransform: function(modelData, elementKey) {
            switch (elementKey) {
                case 'quantity':
                    const precision = this.getElement('quantity').data('precision');
                    return QuantityHelper.formatQuantity(modelData, precision, true);
                default:
                    return modelData;
            }
        },

        onQuantityChange: function(e) {
            this.setModelValueFromElement(e, 'quantity', 'quantity');
        },

        onUnitChange: function() {
            this.setPrecision();
        },

        onProductUnitsChange: function() {
            this.setPrecision();
        },

        setPrecision: function() {
            const precision = this.model.get('product_units')[this.model.get('unit')];
            this.getElement('quantity')
                .data('precision', precision)
                .inputWidget('refresh');
        },

        changeUnitLabel: function() {
            const $unit = this.getElement('unit');
            const unitLabel = this.model.get('unit_label');

            $unit.find('option').each(function() {
                const $option = $(this);
                if (!$option.data('originalText')) {
                    $option.data('originalText', this.text);
                }

                if (unitLabel && this.selected) {
                    this.text = unitLabel;
                } else {
                    this.text = $option.data('originalText');
                }
            });
            $unit.inputWidget('refresh');
        },

        afterProductChanged: function() {
            this.undelegateElementsEvents();
            this.clearElementsCache();
            this.setModelValueFromElements();
            this.delegateElementsEvents();

            this.onLineItemFormEnableChanged();

            this.model.set('product_units', this.getElement('unit').data('unit-precisions'));
            this.setPrecision();
        },

        onLineItemFormEnableChanged: function() {
            if (this.model.get('line_item_form_enable')) {
                this.enableLineItemForm();
            } else {
                this.disableLineItemForm();
            }
        },

        enableLineItemForm: function() {
            this.getLineItemFields().prop('disabled', false).inputWidget('refresh');
            this.getLineItem().removeClass('disabled');
        },

        disableLineItemForm: function() {
            this.getLineItemFields().prop('disabled', true).inputWidget('refresh');
            this.getLineItem().addClass('disabled');
        },

        getLineItem: function() {
            const $innerLineItem = this.getElement('productItem').find(this.elements.lineItem);
            return this.getElement('lineItem').not($innerLineItem);
        },

        getLineItemFields: function() {
            return this.getLineItem().find(this.elements.lineItem);
        },

        dispose: function() {
            delete this.modelAttr;
            delete this.rowId;
            this.disposeElements();
            BaseProductView.__super__.dispose.call(this);
        }
    }));

    return BaseProductView;
});
