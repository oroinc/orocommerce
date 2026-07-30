import localeSettings from 'orolocale/js/locale-settings';
import LineItemProductView from 'oroproduct/js/app/views/line-item-product-view';
import QuantityHelper from 'oroproduct/js/app/quantity-helper';

const OrderKitItemLineItemDraftFormView = LineItemProductView.extend({
    optionNames: LineItemProductView.prototype.optionNames.concat(
        ['minimumQuantity', 'maximumQuantity']
    ),

    elements: {
        id: '[data-name="field__product"]',
        product: '[data-name="field__product"]',
        quantity: '[data-name="field__quantity"]',
        priceValue: '[data-name="field__value"]',
        currency: '[data-name="field__currency"]',
        isPriceChanged: '[data-name="field__is-price-changed"]',
        priceLabelSymbol: '.line-item-price-symbol'
    },

    listen: {
        'pricing:product-price:lock mediator': 'onLineItemProductPriceLock',
        'pricing:product-price:unlock mediator': 'onLineItemProductPriceUnlock'
    },

    modelElements: {
        id: 'id',
        product: 'product',
        quantity: 'quantity',
        priceValue: 'priceValue',
        currency: 'currency'
    },

    modelAttr: {
        id: '',
        product: '',
        quantity: null,
        currency: '',
        unit: '',
        product_units: []
    },

    modelEvents: {
        ...LineItemProductView.prototype.modelEvents,
        'id onProductChanged': ['change', 'onProductChanged'],
        'currency currencyChanged': ['change', 'currencyChanged']
    },

    lineItemModel: null,

    /**
     * @property {number}
     */
    minimumQuantity: 1.0,

    /**
     * @property {number}
     */
    maximumQuantity: null,

    /**
     * @inheritdoc
     */
    constructor: function OrderKitItemLineItemDraftFormView(options) {
        this.setModelValueFromElement = this.setModelValueFromElement.bind(this);
        OrderKitItemLineItemDraftFormView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.lineItemModel = options.productModel || null;
        if (this.lineItemModel === null) {
            throw Error('Option "productModel" cannot be null');
        }

        OrderKitItemLineItemDraftFormView.__super__.initialize.call(this, options);
    },

    handleLayoutInit(options) {
        OrderKitItemLineItemDraftFormView.__super__.handleLayoutInit.call(this, options);
    },

    onProductChanged() {
        OrderKitItemLineItemDraftFormView.__super__.onProductChanged.call(this);

        this.handleDisableFieldsOnProductChange();
    },

    handleDisableFieldsOnProductChange() {
        const modelProductId = this.model.get('id');

        if (modelProductId && this.model.hasChanged('id')) {
            this.disableProductAttributeFields();
        }
    },

    disableProductAttributeFields() {
        this.getElement('quantity').prop('disabled', true);
        this.getElement('priceValue').prop('disabled', true);
    },

    currencyChanged() {
        if (this.model.changed?.currency) {
            const symbol = localeSettings.getCurrencySymbol(this.model.changed?.currency);
            if (!symbol) {
                return;
            }

            this.getElement('priceLabelSymbol').text(symbol);
        }
    },

    onLineItemProductPriceLock($priceValueEl) {
        if ($priceValueEl.is(this.getElement('priceValue'))) {
            this.getElement('isPriceChanged').val(1);
        }
    },

    onLineItemProductPriceUnlock($priceValueEl) {
        if ($priceValueEl.is(this.getElement('priceValue'))) {
            this.getElement('isPriceChanged').val(0);
        }
    },

    modelToViewElementValueTransform(modelData) {
        return modelData;
    },

    /**
     * Keep empty quantity as empty string instead of converting to NaN
     * @inheritDoc
     */
    viewToModelElementValueTransform(elementViewValue) {
        return QuantityHelper.getQuantityNumberOrDefaultValue(elementViewValue, elementViewValue);
    }
});

export default OrderKitItemLineItemDraftFormView;
