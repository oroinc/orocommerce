import BaseView from 'oroui/js/app/views/base/view';
import NumberFormatter from 'orolocale/js/formatter/number';
import __ from 'orotranslation/js/translator';
import _ from 'underscore';
import PricesHelper from 'oropricing/js/app/prices-helper';
import layout from 'oroui/js/layout';
import Popover from 'bootstrap-popover';

const QuickAddRowPricesView = BaseView.extend({
    defaults: {
        defaultQuantity: '',
        subtotalNotAvailable: __('oro.pricing.price.not_available')
    },

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

    constructor: function QuickAddRowPricesView(options) {
        QuickAddRowPricesView.__super__.constructor.call(this, options);
        if (this.model.get('prices')) {
            this.indexPrices();
        }
    },

    initialize(options) {
        this.options = Object.assign({}, this.defaults, _.pick(options, Object.keys(this.defaults)));
        this.elem = Object.assign({}, this.elem, options.elements || {});
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
        this.renderHint();
    },

    checkMinQtyForUnit() {
        const unit = this.model.get('unit');
        if (unit && this.prices && this.prices[unit]) {
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
                priceObj.price * quantity,
                priceObj.currency
            );
        } else if (this.model.get('unit') && this.model.get('unit') !== this.model.get('unit_placeholder')) {
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
        if (!this.pricesHintContentTemplate) {
            this.pricesHintContentTemplate = _.template(this.$(this.elem.pricesHintContent).text());
            const pricesHint = _.template(this.$(this.elem.pricesHint).text())();
            this.$pricesHint = this.$(this.elem.subtotal).after(pricesHint).next();
        }

        const $pricesHint = this.$pricesHint;
        if (!$pricesHint.length || _.isEmpty(this.prices)) {
            return;
        }

        const {prices_index: prices, ...attrs} = this.model.getAttributes();
        attrs.prices = prices;
        const content = this.pricesHintContentTemplate({
            model: attrs,
            prices: this.prices,
            matchedPrice: this.model.get('price'),
            clickable: false,
            formatter: NumberFormatter
        });

        $pricesHint
            .toggleClass('disabled', content.length === 0)
            .attr('disabled', content.length === 0);
        if (!content.length) {
            return;
        }

        if (!$pricesHint.data(Popover.DATA_KEY)) {
            layout.initPopoverForElements($pricesHint, {
                container: 'body',
                forceToShowTitle: true
            }, true);
        }

        $pricesHint.data(Popover.DATA_KEY).updateContent(content);
    }
});

export default QuickAddRowPricesView;
