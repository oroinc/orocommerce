import BaseView from 'oroui/js/app/views/base/view';
import NumberFormatter from 'orolocale/js/formatter/number';
import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import _ from 'underscore';
import PricesHelper from 'oropricing/js/app/prices-helper';
import layout from 'oroui/js/layout';
import Popover from 'bootstrap-popover';
import numeral from 'numeral';

const QuickAddRowPricesView = BaseView.extend({
    defaults: {
        defaultQuantity: '',
        subtotalNotAvailable: __('oro.pricing.price.not_available')
    },

    priceNotFoundTemplate: '<div class="text-center"><%- _.__("oro.pricing.product_prices.price_not_found") %></div>',

    elem: {
        subtotal: '[data-name="field__product-subtotal"]',
        pricesHintContentRendered: '[data-class="prices-hint-content"]'
    },

    listen: {
        'change:prices model': 'indexPrices',
        'change:prices_index model': 'updatePrice',
        'change:quantity model': 'updatePrice',
        'change:unit model': 'updatePrice',
        'change:subtotal model': 'updateUI'
    },

    events: {
        'click [data-role="price-hint-trigger"]': 'updateHintContent',
        'focus [data-role="price-hint-trigger"]': 'updateHintContent'
    },

    constructor: function QuickAddRowPricesView(options) {
        QuickAddRowPricesView.__super__.constructor.call(this, options);
        if (this.model.get('prices')) {
            this.indexPrices();
        }
    },

    initialize(options) {
        this.options = Object.assign({}, this.defaults, _.pick(options, Object.keys(this.defaults)));
        this.elem = Object.assign({}, this.elem, options.elements || {});
        Object.assign(this, _.pick(options, ['pricesHintTemplateSelector', 'pricesHintTemplateContentSelector']));
        QuickAddRowPricesView.__super__.initialize.call(this, options);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        delete this.$pricesHint;

        QuickAddRowPricesView.__super__.dispose.call(this);
    },

    indexPrices() {
        const prices = this.model.get('prices') || {};
        const indexedPrices = PricesHelper.indexPrices(prices);
        this.prices = PricesHelper.preparePrices(indexedPrices);
        this.model.set('prices_index', indexedPrices);
    },

    updatePrice() {
        const quantity = this.model.get('quantity');
        const unit = this.model.get('unit');
        const price = PricesHelper.findPrice(this.prices, unit, quantity);
        this.model.set('price', price);
        this.checkMinQtyForUnit();
        this.model.set('subtotal', this.calcSubtotal());
    },

    checkMinQtyForUnit() {
        const unit = this.model.get('unit');
        const changedManually = this.model.get('quantity_changed_manually');
        if (!changedManually && unit && this.prices && this.prices[unit]) {
            const quantity = this.model.get('quantity');
            const unitPrices = this.prices[unit];
            const index = _.findLastIndex(unitPrices, price => price.quantity);
            if (index !== -1 && quantity < unitPrices[index].quantity) {
                this.model.set('quantity', unitPrices[index].quantity);
            }
        }
    },

    calcSubtotal() {
        const priceObj = this.model.get('price');
        const quantity = this.model.get('quantity');

        if (priceObj && quantity) {
            return NumberFormatter.formatCurrency(
                numeral(priceObj.price).multiply(quantity).value(),
                priceObj.currency
            );
        } else if (this.model.get('unit')) {
            return this.options.subtotalNotAvailable;
        }

        return null;
    },

    updateUI() {
        this.$(this.elem.subtotal).val(this.model.get('subtotal'));

        this.renderHint();

        const $pricesHintEl = this.$(this.elem.pricesHintContentRendered);
        if (this.model.get('subtotal')) {
            $pricesHintEl.show();
        } else {
            $pricesHintEl.hide();
        }
    },

    renderHint() {
        if (this.$pricesHint === void 0) {
            const pricesHint = this.getTemplateFunction('pricesHintTemplate');
            this.$pricesHint = this.$(this.elem.subtotal).after(pricesHint).next();
        }
    },

    updateHintContent(event) {
        if (!this.$pricesHint.length) {
            return;
        }

        const {prices_index: prices, ...attrs} = this.model.getAttributes();

        attrs.prices = prices;

        if (!this.$pricesHint.data(Popover.DATA_KEY)) {
            layout.initPopoverForElements(this.$pricesHint, {
                container: 'body',
                forceToShowTitle: true
            }, true);

            $(event.target).trigger(event);
        }

        const templateName = !_.isEmpty(attrs.prices) ? 'pricesHintTemplateContent' : 'priceNotFoundTemplate';
        const pricesHintContentTemplate = this.getTemplateFunction(templateName);

        this.$pricesHint.data(Popover.DATA_KEY).updateContent(
            pricesHintContentTemplate({
                model: attrs,
                prices: this.prices,
                matchedPrice: this.model.get('price'),
                clickable: false,
                formatter: NumberFormatter
            })
        );
    }
});

export default QuickAddRowPricesView;
