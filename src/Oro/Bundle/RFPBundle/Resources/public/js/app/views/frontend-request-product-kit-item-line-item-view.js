import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import FrontendRequestProductKitItemLineItemContentView
    from 'ororfp/js/app/views/frontend-request-product-kit-item-line-item-content-view';

const FrontendRequestProductKitItemLineItemView = BaseView.extend(_.extend({}, ElementsHelper, {
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'modelAttr'
    ]),

    viewContentSelector: '[data-role="request-product-kit-item-line-item-view"]',

    elements: {
        productId: '[data-name="field__product"]',
        quantity: '[data-name="field__quantity"]'
    },

    modelElements: {
        productId: 'productId',
        quantity: 'quantity'
    },

    modelAttr: {
        kitItemId: 0,
        kitItemLabel: '',
        isVisible: false,
        isOptional: true,
        isPendingAdd: false,
        isPendingRemove: false,
        sortOrder: 0,
        productId: 0,
        productSku: 0,
        productName: 0,
        quantity: 0,
        productUnit: '',
        isValid: true
    },

    events: {
        'content:remove': 'onRemove'
    },

    /**
     * @property {jQuery.Element}
     */
    $viewContent: null,

    /**
     * @property {Backbone.Model}
     */
    model: null,

    /**
     * @property {Backbone.Model}
     */
    requestProductModel: null,

    /**
     * @property {Backbone.Collection}
     */
    kitItemLineItems: null,

    constructor: function FrontendRequestProductKitItemLineItemView(options) {
        FrontendRequestProductKitItemLineItemView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        FrontendRequestProductKitItemLineItemView.__super__.initialize.call(this, options);

        this.deferredInitializeCheck(options, ['requestProductModel', 'kitItemLineItems']);
    },

    /**
     * @inheritdoc
     */
    deferredInitialize: function(options) {
        this.initModel(options);
        this.initializeElements(options);

        this.$viewContent = this.$el.find(this.viewContentSelector);

        this.kitItemLineItems.add(this.model, {merge: true});

        this.listenTo(this.model, {
            'state:softRemove': this.onSoftRemove,
            'state:apply': this.onStateApply,
            'state:revert': this.onStateRevert
        });

        this.render();
    },

    /**
     * @inheritdoc
     */
    initModel: function(options) {
        ElementsHelper.initModel.call(this, options);

        this.requestProductModel = options.requestProductModel;
        this.kitItemLineItems = options.kitItemLineItems;
    },

    /**
     * @inheritdoc
     */
    render: function() {
        this.subview(
            'viewContent',
            new FrontendRequestProductKitItemLineItemContentView({
                el: this.$viewContent,
                modelAttr: this.model.toJSON()
            })
        );
    },

    onRemove: function() {
        this.kitItemLineItems?.remove(this.model);
    },

    onSoftRemove: function() {
        this.model.set('isPendingRemove', true);
        this.$el.addClass('hidden');
        this.$el.find(':input[data-name]').attr('disabled', true);
    },

    onStateRevert: function() {
        if (this.model.get('isPendingAdd')) {
            this.$el.trigger('content:remove').remove();
        } else if (this.model.get('isPendingRemove')) {
            this.model.set('isPendingRemove', false);
            this.$el.removeClass('hidden');
            this.$el.find(':input[data-name]').removeAttr('disabled');
        }
    },

    onStateApply: function() {
        if (this.model.get('isPendingRemove')) {
            this.$el.trigger('content:remove').remove();
        } else if (this.model.get('isPendingAdd')) {
            this.model.set('isPendingRemove', false);
            this.model.set('isPendingAdd', false);
        }
    }
}));

export default FrontendRequestProductKitItemLineItemView;
