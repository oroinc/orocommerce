import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import $ from 'jquery';
import _ from 'underscore';
import routing from 'routing';
import RequestProductItemsCollection from 'oroui/js/app/models/base/collection';
import KitItemLineItemsCollection from 'ororfp/js/app/models/frontend-product-kit-item-line-item-collection';
import FrontendRequestProductTierPrices from 'ororfp/js/app/utils/frontend-request-product-tier-prices';
import FrontendRequestProductModesView from 'ororfp/js/app/views/frontend-request-product-modes-view';

const FrontendRequestProductView = BaseView.extend(_.extend({}, ElementsHelper, {
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'productUnitsRoute'
    ]),

    productUnitsRoute: 'oro_product_frontend_ajaxproductunit_productunits',

    requestProductContainerSelector: '[data-role="request-product-container"]',

    requestProductItemsContainerSelector: '[data-role="request-product-items-container"]',

    requestProductItemSelector: '[data-role="request-product-item"]',

    addRequestProductItemButtonSelector: '[data-role="request-product-item-add"]',

    removeOfferLineItemSelector: '[data-role="request-product-item-remove"]',

    /**
     * @property {$.Element}
     */
    $requestProductItemsContainer: null,

    /**
     * @property {$.Element}
     */
    $addRequestProductItemButton: null,

    elements: {
        productId: '[data-name="field__product"]:first',
        comment: '[data-name="field__comment"]',
        fieldCommentCheckbox: '[data-role="field__comment-checkbox"]'
    },

    elementsEvents: {
        'fieldCommentCheckbox onFieldCommentCheckboxChange': ['change', 'onFieldCommentCheckboxChange']
    },

    modelElements: {
        productId: 'productId',
        comment: 'comment'
    },

    modelAttr: {
        index: 0,
        mode: 'edit',
        productId: 0,
        productType: 'simple',
        productSku: '',
        productName: '',
        comment: '',
        requestProductItems: null,
        kitItemLineItems: null,
        requestProductItemsPrices: {},
        productUnits: {}
    },

    modelEvents: {
        'productId onChangeProduct': ['change', 'onChangeProduct']
    },

    /**
     * @property {Backbone.Model}
     */
    model: null,

    /**
     * @property {Backbone.Collection}
     */
    kitItemLineItems: null,

    /**
     * @property {Backbone.Collection}
     */
    requestProductItems: null,

    /**
     * @property {jQuery.Promise}
     */
    requestTierPricesDeferred: null,

    /**
     * @property {Object}
     */
    savedAttributes: {},

    /**
     * @inheritdoc
     */
    constructor: function FrontendRequestProductView(options) {
        FrontendRequestProductView.__super__.constructor.call(this, options);
    },

    /**
     * @param {Object} options
     */
    initialize: function(options) {
        FrontendRequestProductView.__super__.initialize.call(this, options);

        this.$requestProductItemsContainer = this.$el.find(this.requestProductItemsContainerSelector);
        this.$addRequestProductItemButton = this.$el.find(this.addRequestProductItemButtonSelector);

        this.initModel(options);
        this.initializeElements(options);

        this.delegate('content:initialized', this.requestProductItemsContainerSelector, this.onContentInitialized);
        this.delegate('rfp:request-tier-prices', this.onRequestTierPrices);

        this.subview('viewModes', new FrontendRequestProductModesView({
            el: this.$el,
            requestProductModel: this.model,
            kitItemLineItems: this.kitItemLineItems,
            requestProductItems: this.requestProductItems
        }));

        this.initializeSubviews({
            requestProductModel: this.model,
            kitItemLineItems: this.kitItemLineItems,
            requestProductItems: this.requestProductItems,
            modelAttr: {
                productUnits: this.model.get('productUnits')
            }
        });
    },

    onContentInitialized: function(e) {
        $(e.target).trigger('deferredInitialize', {
            requestProductModel: this.model,
            kitItemLineItems: this.kitItemLineItems,
            requestProductItems: this.requestProductItems,
            modelAttr: {
                productUnits: this.model.get('productUnits'),
                isPendingAdd: true
            }
        });
    },

    /**
     * @inheritdoc
     */
    handleLayoutInit: function() {
        this.createInputWidgets();

        if (!this.$el.find('.validation-failed').length && !_.isEmpty(this.model.get('productId'))) {
            this.switchMode('view');
        }

        this.toggleAddRequestProductItemButton();
        this._resolveDeferredRender();
    },

    createInputWidgets: function() {
        this.$el.attr('data-skip-input-widgets', null).inputWidget('seekAndCreate');
    },

    onRequestTierPrices: function(e, payload, successCallback) {
        if (this.disposed) {
            return;
        }

        this.requestTierPrices(payload, successCallback);
    },

    requestTierPrices: function(payload, successCallback) {
        if (this.requestTierPricesDeferred?.state() !== 'pending') {
            this.requestTierPricesDeferred = $.Deferred();
        }

        if (!payload?.length) {
            payload = this.$el.find(this.requestProductItemSelector).find(':input[data-name]').serializeArray();
        }

        if (payload.length) {
            payload = _.union(
                this.$el.find(this.requestProductContainerSelector).find(':input[data-name]').serializeArray(),
                payload
            );

            FrontendRequestProductTierPrices
                .requestTierPrices(payload)
                .done(response => {
                    if (this.disposed) {
                        return;
                    }

                    const tierPrices = response.tierPrices ?? {};

                    this.requestTierPricesDeferred.resolve(tierPrices[this.model.get('index')] ?? {});
                })
                .fail(jqXHR => {
                    if (this.disposed) {
                        return;
                    }

                    this.requestTierPricesDeferred.resolve({});
                });
        }

        if (successCallback) {
            this.requestTierPricesDeferred.done(tierPrices => successCallback(tierPrices));
        }

        return this.requestTierPricesDeferred.promise();
    },

    initModel: function(options) {
        this.requestProductItems = new RequestProductItemsCollection();
        this.kitItemLineItems = new KitItemLineItemsCollection();

        options.modelAttr = _.extend(
            {},
            options.modelAttr || {},
            {
                productSku: options.modelAttr.productSku,
                productName: options.modelAttr.productName
            }
        );

        ElementsHelper.initModel.call(this, options);
    },

    toggleAddRequestProductItemButton: function() {
        this.$addRequestProductItemButton.toggleClass(
            'hidden',
            Boolean(this.model.get('productId')) === false || this.model.get('productType') === 'kit'
        );
    },

    onFieldCommentCheckboxChange(event) {
        const commentChecked = $(event.target).prop('checked');

        if (commentChecked) {
            this.model.set('comment', this.getElement('comment').val());
        } else {
            this.model.set('comment', '', {silent: true});
        }
    },

    onChangeProduct: function(data) {
        if (data?.event?.added) {
            this.model.set('productSku', _.unescape(data.event.added.sku || ''));
            this.model.set('productName', _.unescape(data.event.added['defaultName.string'] || ''));
            this.model.set('productType', data.event.added.type || 'simple');

            if (this.model.get('productType') === 'kit' && this.$requestProductItemsContainer.children().length > 1) {
                this.$requestProductItemsContainer.find(this.removeOfferLineItemSelector).slice(1).trigger('click');
            }

            if (this.model.get('productId') && !this.$requestProductItemsContainer.children().length) {
                this.$addRequestProductItemButton.trigger('click');
            }
        }

        if (this.model.get('productId')) {
            this.requestProductUnits(true);
        }

        this.toggleAddRequestProductItemButton();
    },

    /**
     * @param {boolean} [force]
     */
    requestProductUnits: function(force) {
        const productId = this.model.get('productId');
        if (!productId) {
            return $.Deferred().reject().promise();
        }

        if (!force && !_.isEmpty(this.model.get('productUnits'))) {
            return $.Deferred().reject().promise();
        }

        return $.ajax({
            url: routing.generate(this.productUnitsRoute, {id: productId}),
            type: 'GET',
            success: response => {
                this.model.set('productUnits', response.units ?? {});
            }
        });
    },

    switchMode: function(mode) {
        if (this.requestTierPricesDeferred) {
            this.requestTierPricesDeferred.done(() => this.subview('viewModes').switchMode(mode));
        } else {
            this.subview('viewModes').switchMode(mode);
        }
    }
}));

export default FrontendRequestProductView;
