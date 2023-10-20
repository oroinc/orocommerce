import $ from 'jquery';
import LineItemProductPricesView from 'oropricing/js/app/views/line-item-product-prices-view';
import NumberFormatter from 'orolocale/js/formatter/number';
import matchedPrice from 'tpl-loader!oropricing/templates/product/line-item-matched-price.html';
import perUnit from 'tpl-loader!oropricing/templates/product/line-item-per-unit.html';

const LineItemProductPricesExtendedView = LineItemProductPricesView.extend({
    className: 'line-item-product-prices-extended',

    constructor: function LineItemProductPricesExtendedView(...args) {
        LineItemProductPricesExtendedView.__super__.constructor.apply(this, args);
    },

    render() {
        LineItemProductPricesExtendedView.__super__.render.call(this);

        this.$elements['perUnit'] = $(perUnit({
            unit: this.model.get('unit')
        }));

        this.getElement('priceValue').attr('data-floating-error', true);
        this.getElement('pricesHint').after(this.$elements['perUnit']);

        this.$el.addClass(this.className);
    },

    onUnitChange(options) {
        LineItemProductPricesExtendedView.__super__.onUnitChange.call(this, options);

        this.getElement('perUnit').replaceWith(perUnit({
            unit: this.model.get('unit')
        }));
    },

    updateUI() {
        LineItemProductPricesExtendedView.__super__.updateUI.call(this);

        this.toggleAndUpdateMatchedPrice();
    },

    toggleAndUpdateMatchedPrice() {
        if (this.$elements['matchedPrice']) {
            this.$elements['matchedPrice'].remove();
            delete this.$elements['matchedPrice'];
        }

        if (this.isOverriddenPrice()) {
            const {price, currency} = this.findPrice();

            this.$elements['matchedPrice'] = $(matchedPrice({
                matchedPrice: NumberFormatter.formatCurrency(price, currency)
            }));

            this.$el.append(this.$elements['matchedPrice']);
        }
    },

    isOverriddenPrice() {
        const price = this.findPriceValue();
        const priceValue = NumberFormatter.unformatStrict(this.model.get('price'));

        return price !== null && this.calcTotalPrice(price) !== this.calcTotalPrice(priceValue);
    }
});

export default LineItemProductPricesExtendedView;
