import BaseModel from 'oroui/js/app/models/base/model';

const OrderLineItemDraftModel = BaseModel.extend({
    defaults: {
        id: '',
        drySubmitTrigger: '',
        isFreeForm: '',
        productSku: '',
        freeFormProduct: '',
        product: '',
        quantity: '',
        productUnit: '',
        priceValue: null,
        priceCurrency: '',
        isPriceChanged: '',
        priceType: '',
        shipBy: '',
        comment: ''
    },

    idAttribute: 'mId',

    /**
     * @inheritdoc
     */
    constructor: function OrderLineItemDraftModel(attrs, options) {
        OrderLineItemDraftModel.__super__.constructor.call(this, attrs, options);
    }
});

export default OrderLineItemDraftModel;
