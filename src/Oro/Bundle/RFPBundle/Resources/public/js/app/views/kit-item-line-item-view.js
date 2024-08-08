import LineItemProductView from 'oroproduct/js/app/views/line-item-product-view';

const KitItemLineItemView = LineItemProductView.extend({
    optionNames: LineItemProductView.prototype.optionNames.concat(
        ['minimumQuantity', 'maximumQuantity']
    ),

    elements: {
        id: '[data-name="field__product"]',
        product: '[data-name="field__product"]',
        quantity: '[data-name="field__quantity"]'
    },

    modelElements: {
        id: 'id',
        product: 'product',
        quantity: 'quantity'
    },

    modelAttr: {
        id: '',
        product: '',
        quantity: null,
        unit: '',
        product_units: []
    },

    modelEvents: {
        ...LineItemProductView.prototype.modelEvents,
        'id onProductChange': ['change', 'onProductChange']
    },

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
        KitItemLineItemView.__super__.initialize.call(this, options);
    },

    handleLayoutInit(options) {
        KitItemLineItemView.__super__.handleLayoutInit.call(this, options);
    },

    onProductChange() {
        if (!this.model.get('id')) {
            this.model.set('quantity', null, {silent: true});

            this.getElement('quantity').attr('disabled', true).val(null);

            this.getElement('quantity').valid();
        } else {
            this.resetData();

            this.getElement('quantity').prop('disabled', false);
        }
    },

    resetData() {
        this.model.set('quantity', this.minimumQuantity);
        this.getElement('quantity').valid();
    },

    modelToViewElementValueTransform(modelData) {
        return modelData;
    },

    viewToModelElementValueTransform(elementViewValue) {
        return elementViewValue;
    }
});

export default KitItemLineItemView;
