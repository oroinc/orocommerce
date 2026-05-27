import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import HtmlCell from 'orodatagrid/js/datagrid/cell/html-cell';
import Popover from 'bootstrap-popover';
import layout from 'oroui/js/layout';
import pricesHelper from 'oropricing/js/app/prices-helper';
import NumberFormatter from 'orolocale/js/formatter/number';
import pricesTierTableTemplate from 'tpl-loader!oropricing/templates/product/prices-tier-table.html';

const DraftLineItemPriceCell = HtmlCell.extend({
    /**
     * Selector for the element that triggers tier prices popover
     */
    HINT_SELECTOR: '[data-role="tier-prices-hint"]',

    /**
     * @inheritdoc
     */
    constructor: function DraftLineItemPriceCell(...args) {
        DraftLineItemPriceCell.__super__.constructor.apply(this, args);
    },

    /**
     * @inheritdoc
     */
    render() {
        DraftLineItemPriceCell.__super__.render.call(this);
        this._initTierPricesPopover();

        return this;
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        const $hint = this.$(this.HINT_SELECTOR);
        if ($hint.length && $hint.data(Popover.DATA_KEY)) {
            $hint.popover('dispose');
        }

        DraftLineItemPriceCell.__super__.dispose.call(this);
    },

    _initTierPricesPopover() {
        const $hint = this.$(this.HINT_SELECTOR);
        if (!$hint.length || $hint.data(Popover.DATA_KEY)) {
            return;
        }

        const content = this._buildPopoverContent($hint);
        if (!content) {
            return;
        }

        $hint.attr('data-content', content);

        layout.initPopoverForElements($hint, {
            animation: false,
            html: true,
            trigger: 'manual',
            container: 'body',
            placement: $hint.data('placement') || 'bottom',
            close: false
        });
    },

    /**
     * Builds the HTML content for the tier-prices popover.
     *
     * @param {jQuery} $hint
     * @returns {string|null}
     */
    _buildPopoverContent($hint) {
        const tierPrices = $hint.data('tier-prices') || [];
        if (!tierPrices.length) {
            return null;
        }

        const quantity = parseFloat($hint.data('quantity')) || 1;
        const unit = $hint.data('unit') || '';

        const prices = pricesHelper.preparePrices(tierPrices);
        const unitPrices = {};
        if (prices[unit]) {
            unitPrices[unit] = pricesHelper.sortByLowQuantity(prices[unit]);
        }

        if (_.isEmpty(unitPrices)) {
            return null;
        }

        return pricesTierTableTemplate({
            prices: unitPrices,
            matchedPrice: pricesHelper.findPrice(prices, unit, quantity),
            clickable: false,
            alwaysShowHead: true,
            title: __('oro.pricing.product_prices.price_is_overridden'),
            formatter: NumberFormatter,
            ariaControlsId: null
        });
    }
});

export default DraftLineItemPriceCell;
