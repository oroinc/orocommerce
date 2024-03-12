import mediator from 'oroui/js/mediator';
import LineItemProductView from 'oroproduct/js/app/views/line-item-product-view';

const KitItemLineItemView = LineItemProductView.extend({
    optionNames: LineItemProductView.prototype.optionNames.concat(
        ['minimumQuantity', 'maximumQuantity']
    ),

    elements: {
        id: '[data-name="field__product"]',
        product: '[data-name="field__product"]',
        quantity: '[data-name="field__quantity"]',
        priceValue: '[data-name="field__value"]',
        currency: '[data-name="field__currency"]',
        isPriceChanged: '[data-name="field__is-price-changed"]'
    },

    listen: {
        'pricing:product-price:lock mediator': 'lineItemProductPriceLock',
        'pricing:product-price:unlock mediator': 'lineItemProductPriceUnlock'
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
        'id onProductChange': ['change', 'onProductChange'],
        'id resetChecksum': ['change', 'resetChecksum'],
        'quantity resetChecksum': ['change', 'resetChecksum'],
        'priceValue resetChecksum': ['change', 'resetChecksum']
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
    constructor: function KitItemLineItemView(options) {
        this.setModelValueFromElement = this.setModelValueFromElement.bind(this);
        KitItemLineItemView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.lineItemModel = options.productModel || null;
        if (this.lineItemModel === null) {
            throw Error('Option "productModel" cannot be null');
        }

        KitItemLineItemView.__super__.initialize.call(this, options);
    },

    handleLayoutInit(options) {
        this.entryPointTriggers([
            this.getElement('product'),
            this.getElement('quantity'),
            this.getElement('priceValue')
        ]);

        KitItemLineItemView.__super__.handleLayoutInit.call(this, options);
    },

    /**
     * @param {jQuery.Element} $fields
     */
    entryPointTriggers($fields) {
        for (const $field of $fields) {
            $field.attr('data-entry-point-trigger', true);
        }
    },

    onProductChange() {
        if (!this.model.get('id')) {
            this.model.set('quantity', null, {silent: true});
            this.model.set('priceValue', null, {silent: true});

            this.getElement('quantity').attr('disabled', true).val(null);
            this.getElement('priceValue').attr('disabled', true).val(null);

            this.getElement('quantity').valid();
            this.getElement('priceValue').valid();
        } else {
            this.resetData();

            this.getElement('quantity').removeAttr('disabled', false);
            this.getElement('priceValue').removeAttr('disabled', false);
        }
    },

    resetChecksum() {
        if (this.lineItemModel.get('checksum')) {
            this.lineItemModel.set('checksum', '');
        }
    },

    resetData() {
        mediator.trigger('entry-point:listeners:off');

        this.getElement('priceValue').addClass('matched-price');

        this.model.set('quantity', this.minimumQuantity);
        this.getElement('quantity').valid();

        mediator.trigger('entry-point:order:trigger');
        mediator.trigger('entry-point:listeners:on');
    },

    lineItemProductPriceLock() {
        this.getElement('isPriceChanged').val(1);
    },

    lineItemProductPriceUnlock() {
        this.getElement('isPriceChanged').val(0);
    },

    modelToViewElementValueTransform(modelData) {
        return modelData;
    },

    viewToModelElementValueTransform(elementViewValue) {
        return elementViewValue;
    }
});

export default KitItemLineItemView;
