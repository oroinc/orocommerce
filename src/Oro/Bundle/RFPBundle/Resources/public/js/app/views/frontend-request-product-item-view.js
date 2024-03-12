import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import UnitsUtil from 'oroproduct/js/app/units-util';
import quantityHelper from 'oroproduct/js/app/quantity-helper';

const FrontendRequestProductItemView = BaseView.extend(_.extend({}, ElementsHelper, {
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'modelAttr'
    ]),

    elements: {
        quantity: '[data-name="field__quantity"]',
        productUnit: '[data-name="field__product-unit"]',
        removeButton: '[data-role="request-product-item-remove"]'
    },

    elementsEvents: {
        'removeButton onClickRemoveButton': ['click', 'onClickRemoveButton']
    },

    modelElements: {
        quantity: 'quantity',
        productUnit: 'productUnit'
    },

    modelAttr: {
        index: 0,
        productUnit: '',
        quantity: 0,
        isPendingAdd: false,
        isPendingRemove: false,
        productUnits: {}
    },

    modelEvents: {
        'productUnit onChangeProductUnit': ['change', 'onChangeProductUnit'],
        'quantity onChangeQuantity': ['change', 'onChangeQuantity']
    },

    events: {
        'content:remove': 'onRemove'
    },

    /**
     * @property {Backbone.Model}
     */
    requestProductModel: null,

    /**
     * @property {Backbone.Collection}
     */
    kitItemLineItems: null,

    /**
     * @property {Backbone.Collection}
     */
    requestProductItems: null,

    /**
     * @property {Backbone.Model}
     */
    model: null,

    /**
     * @property {Object}
     */
    savedAttributes: {},

    constructor: function FrontendRequestProductItemView(options) {
        FrontendRequestProductItemView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.deferredInitializeCheck(options, ['requestProductModel', 'requestProductItems', 'kitItemLineItems']);
    },

    /**
     * @inheritdoc
     */
    deferredInitialize: function(options) {
        this.initModel(options);
        this.initializeElements(options);

        this.getElement('quantity').inputWidget('create');
        this.updatePrecision();
        this.onChangeQuantity();

        this.initializeSubviews({
            requestProductItemModel: this.model,
            requestProductModel: this.requestProductModel,
            kitItemLineItems: this.kitItemLineItems
        });

        this.listenTo(this.model, {
            'state:softRemove': this.onSoftRemove,
            'state:save': this.onStateSave,
            'state:apply': this.onStateApply,
            'state:revert': this.onStateRevert
        });

        this.listenTo(this.requestProductModel, {
            'change:productUnits': this.onChangeProductUnits
        });

        this.onChangeProductUnits();
    },

    initModel: function(options) {
        ElementsHelper.initModel.call(this, options);

        this.requestProductModel = options.requestProductModel;
        this.requestProductItems = options.requestProductItems;
        this.kitItemLineItems = options.kitItemLineItems;

        this.requestProductItems.add(this.model, {merge: true});
    },

    onChangeProductUnits: function() {
        // Snake_case is used on purpose to comply with UnitsUtil.
        this.model.set('product_units', this.requestProductModel.get('productUnits'));
        UnitsUtil.updateSelect(this.model, this.getElement('productUnit'));
    },

    onChangeProductUnit: function() {
        this.updatePrecision();
    },

    onChangeQuantity: function(e) {
        this.setModelValueFromElement(e, 'quantity', 'quantity');
    },

    updatePrecision: function() {
        const precision = this.requestProductModel.get('productUnits')[this.model.get('productUnit')] ?? 0;

        this.getElement('quantity')
            .data('precision', precision)
            .inputWidget('refresh');
    },

    viewToModelElementValueTransform: function(elementViewValue, elementKey) {
        switch (elementKey) {
            case 'quantity':
                const $element = this.getElement(elementKey);
                if ($element.attr('type').toLowerCase() === 'number') {
                    return parseFloat(elementViewValue);
                }

                return quantityHelper.getQuantityNumberOrDefaultValue(elementViewValue, NaN);
            default:
                return elementViewValue;
        }
    },

    modelToViewElementValueTransform: function(modelData, elementKey) {
        switch (elementKey) {
            case 'quantity':
                const precision = this.getElement('quantity').data('precision');

                return quantityHelper.formatQuantity(modelData, precision, true);
            default:
                return modelData;
        }
    },

    onRemove: function() {
        this.requestProductItems.remove(this.model);
    },

    /**
     * @param {jQuery.Event} e
     */
    onClickRemoveButton: function(e) {
        e.preventDefault();
        e.stopPropagation();

        this.onSoftRemove();
    },

    onSoftRemove: function() {
        if (!this.model.get('isPendingAdd')) {
            this.model.set('isPendingRemove', true);

            this.$el.addClass('hidden');
            this.$el.find(':input[data-name]').attr('disabled', true);
        } else {
            this.$el.trigger('content:remove').remove();
        }
    },

    onStateSave: function() {
        this.model.set('isPendingAdd', false);
        this.savedAttributes = $.extend(true, {}, this.model.attributes);
    },

    onStateRevert: function() {
        if (this.model.get('isPendingAdd')) {
            this.$el.trigger('content:remove').remove();

            return;
        }

        if (this.model.get('isPendingRemove')) {
            this.model.set('isPendingRemove', false);
            this.$el.removeClass('hidden');
            this.$el.find(':input[data-name]').removeAttr('disabled');
        }

        if (!_.isEmpty(this.savedAttributes)) {
            this.model.set(this.savedAttributes);
        }
    },

    onStateApply: function() {
        if (this.model.get('isPendingRemove')) {
            this.$el.trigger('content:remove').remove();
        } else if (this.model.get('isPendingAdd')) {
            this.model.set('isPendingAdd', false);
        }
    }
}));

export default FrontendRequestProductItemView;
