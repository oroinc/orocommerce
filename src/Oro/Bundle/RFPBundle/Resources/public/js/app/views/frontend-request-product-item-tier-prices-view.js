import _ from 'underscore';
import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import NumberFormatter from 'orolocale/js/formatter/number';
import Popover from 'bootstrap-popover';
import layout from 'oroui/js/layout';
import PricesHelper from 'oropricing/js/app/prices-helper';

const FrontendRequestProductItemTierPricesView = BaseView.extend(_.extend({}, ElementsHelper, {
    keepElement: true,

    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'ariaControlsId', 'pricesHintTemplateSelector', 'pricesHintContentTemplateSelector'
    ]),

    ariaControlsId: '',

    pricesHintTemplateSelector: '',

    pricesHintContentTemplateSelector: '',

    requestProductItemSelector: '[data-role="request-product-item"]',

    elements: {
        priceValue: '[data-name="field__value"]',
        unit: '[data-name="field__product-unit"]',
        currency: '[data-name="field__currency"]'
    },

    modelEvents: {
        'quantity onChangeQuantity': ['change', 'onChangeQuantity'],
        'productUnit onRequestTierPrices': ['change', 'onRequestTierPrices'],
        'prices onChangePrices': ['change', 'onChangePrices']
    },

    modelElements: {
        price: 'priceValue',
        currency: 'currency'
    },

    /**
     * @property {Function}
     */
    pricesHintTemplate: null,

    /**
     * @property {Function}
     */
    pricesHintContentTemplate: null,

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

    /**
     * @property {Object}
     */
    pricesByUnit: null,

    /**
     * @property {Object}
     */
    matchingPrice: null,

    isTierPricesPopoverInitialized: false,

    /**
     * @property {jQuery.Deferred}
     */
    requestTierPricesDeferred: null,

    /**
     * @property {jQuery.Element}
     */
    $pricesHint: null,

    constructor: function FrontendRequestProductItemTierPricesView(options) {
        FrontendRequestProductItemTierPricesView.__super__.constructor.call(this, options);
    },

    events: function() {
        const events = {};

        events[Popover.Event.SHOWN + ' .product-tier-prices'] = 'onPopoverShow';
        events[Popover.Event.HIDDEN + ' [data-toggle=popover]'] = 'onPopoverHide';

        return events;
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        FrontendRequestProductItemTierPricesView.__super__.initialize.call(this, options);

        this.pricesHintTemplate = _.template($(this.pricesHintTemplateSelector).html());
        this.pricesHintContentTemplate = _.template($(this.pricesHintContentTemplateSelector).html());

        this.onRequestTierPrices = _.debounce(this.onRequestTierPrices.bind(this), 100);

        this.deferredInitializeCheck(options, ['requestProductItemModel', 'requestProductModel', 'kitItemLineItems']);
    },

    /**
     * @inheritdoc
     */
    deferredInitialize: function(options) {
        this.initModel(options);
        if (!this.model) {
            return;
        }

        this.initializeElements(options);

        this.listenTo(this.requestProductModel, 'change:productId', this.onRequestTierPrices);
        this.listenTo(this.model, 'state:revert', this.onRequestTierPrices);
        this.listenTo(this.kitItemLineItems, {
            'update': this.onRequestTierPrices,
            'state:revert': this.onRequestTierPrices
        });

        this.requestTierPrices();
    },

    initModel: function(options) {
        this.model = options.requestProductItemModel;
        this.requestProductModel = options.requestProductModel;
        this.kitItemLineItems = options.kitItemLineItems;
    },

    requestTierPrices: function() {
        if (this.model.get('isPendingRemove') ||
            !this.requestProductModel.get('productId') ||
            !this.model.get('productUnit')) {
            return;
        }

        this.$el.trigger(
            'rfp:request-tier-prices',
            [
                this.$el.closest(this.requestProductItemSelector).find(':input[data-name]').serializeArray(),
                tierPrices => {
                    if (this.disposed) {
                        return;
                    }

                    this.model.set('prices', tierPrices[this.model.get('index')] ?? []);
                }
            ]
        );
    },

    render: function() {
        this.onChangePrices();
    },

    renderTierPricesPopover: function() {
        if (!this.isTierPricesPopoverInitialized) {
            this.initTierPricesPopover();
        }

        const content = this.getTierPricesPopoverContent();

        this.$pricesHint
            .toggleClass('disabled', content.length === 0)
            .attr('disabled', content.length === 0);

        if (!content.length) {
            return;
        }

        if (!this.$pricesHint.data(Popover.DATA_KEY)) {
            layout.initPopoverForElements(this.$pricesHint, {
                container: 'body',
                forceToShowTitle: true
            }, true);
        }

        this.$pricesHint.data(Popover.DATA_KEY).updateContent(content);
    },

    initTierPricesPopover: function() {
        this.isTierPricesPopoverInitialized = true;

        // Filtering comments out in dev mode
        this.$pricesHint = $($(this.pricesHintTemplate()).toArray().filter(el => {
            return el.nodeType === Node.ELEMENT_NODE;
        })[0]);

        const productSku = this.model.get('productSku');
        if (productSku) {
            this.updateTierPricesPopoverAriaLabel(productSku);
        }

        this.getElement('priceValue').after(this.$pricesHint);

        this.model.on('change:productSku', this.onChangeProductSku, this);
    },

    /**
     * @returns {string}
     */
    getTierPricesPopoverContent: function() {
        if (_.isEmpty(this.pricesByUnit)) {
            return '';
        }

        return this.pricesHintContentTemplate({
            model: this.model.toJSON(),
            prices: PricesHelper.sortUnitPricesByLowQuantity(this.pricesByUnit),
            matchedPrice: this.findMatchingPrice(),
            clickable: true,
            formatter: NumberFormatter,
            ariaControlsId: this.ariaControlsId
        });
    },

    /**
     * @param {Backbone.Model} model
     * @param {string} newValue
     */
    onChangeProductSku: function(model, newValue) {
        this.updateTierPricesPopoverAriaLabel(newValue);
    },

    /**
     * @param {string} productSku
     */
    updateTierPricesPopoverAriaLabel: function(productSku) {
        if (!this.$pricesHint) {
            return;
        }

        this.$pricesHint.attr('aria-label', _.__('oro.pricing.view_all_prices_extended', {
            product_attrs: productSku
        }));
    },

    onChangePrices: function() {
        this.pricesByUnit = PricesHelper.preparePrices(this.model.get('prices'));
        this.matchingPrice = {};

        this.findMatchingPrice();
        this.renderTierPricesPopover();
    },

    onRequestTierPrices: function() {
        if (this.disposed) {
            return;
        }

        this.requestTierPrices();
    },

    onChangeQuantity: function() {
        this.findMatchingPrice();
        this.renderTierPricesPopover();
    },

    /**
     * @param {jQuery.Event} event
     */
    onPopoverShow: function(event) {
        const eventNamespace = this.eventNamespace();
        const popover = $(event.target).data(Popover.DATA_KEY);
        const self = this;

        $(popover.getTipElement())
            .off(eventNamespace)
            .on('click' + eventNamespace, 'a', function(e) {
                e.preventDefault();
                popover.hide();
                self.setPriceFromHint(this);
            });
    },

    /**
     * @param {jQuery.Event} event
     */
    onPopoverHide: function(event) {
        const eventNamespace = this.eventNamespace();
        const tip = $(event.target).data(Popover.DATA_KEY).getTipElement();

        $(tip).off(eventNamespace);
    },

    /**
     * @returns {Object}
     */
    findMatchingPrice: function() {
        const quantity = this.model.get('quantity');
        const unit = this.model.get('productUnit');

        const priceKey = unit + ' ' + quantity;

        if (!this.matchingPrice[priceKey]) {
            this.matchingPrice[priceKey] = PricesHelper.findPrice(this.pricesByUnit, unit, quantity);
        }

        this.model.set('matchingPrice', this.matchingPrice[priceKey]?.price);
        this.getElement('priceValue').data('found_price', this.matchingPrice[priceKey]);

        return this.matchingPrice[priceKey];
    },

    setPriceFromHint: function(priceElement) {
        const $priceElement = $(priceElement);

        this.model.set('productUnit', $priceElement.data('unit'));
        this.model.set('price', NumberFormatter.formatMonetary($priceElement.data('price')));

        this.getElement('priceValue').trigger('change');
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        delete this.templates;
        delete this.modelAttr;
        delete this.pricesByUnit;
        delete this.matchingPrice;

        this.disposeElements();

        FrontendRequestProductItemTierPricesView.__super__.dispose.call(this);
    }
}));

export default FrontendRequestProductItemTierPricesView;
