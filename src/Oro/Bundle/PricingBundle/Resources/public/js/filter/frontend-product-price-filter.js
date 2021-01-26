import ProductPriceFilter from 'oro/filter/product-price-filter';
import template from 'tpl-loader!oropricing/templates/product/pricing-range-filter.html';
import unitTemplate from 'tpl-loader!oropricing/templates/product/pricing-range-units-filter.html';
import localeSettings from 'orolocale/js/locale-settings';
import tools from 'oroui/js/tools';

/**
 * Frontend product price filter
 *
 * @export  oro/filter/product-price-filter
 * @class   oro.filter.FrontendProductPriceFilter
 * @extends oro.filter.ProductPriceFilter
 */
const FrontendProductPriceFilter = ProductPriceFilter.extend({
    /**
     * @inheritDoc
     */
    template: template,

    /**
     * @inheritDoc
     */
    unitTemplate: unitTemplate,

    /**
     * @inheritDoc
     */
    criteriaValueSelectors: {
        ...ProductPriceFilter.prototype.criteriaValueSelectors,
        type: '[data-choice-value-select]'
    },

    typeValues: {
        ...ProductPriceFilter.prototype.typeValues,
        moreThan: 2,
        lessThan: 6,
        equalsOrMoreThan: 1,
        equalsOrLessThan: 5
    },

    events: {
        'change [data-choice-value-select]': '_onChangeChoiceValue'
    },

    showChoices: true,

    /**
     * @inheritDoc
     */
    constructor: function FrontendProductPriceFilter(options) {
        FrontendProductPriceFilter.__super__.constructor.call(this, options);
    },

    swapValues(data) {
        if (!this.isApplicable(data.type)) {
            return data;
        }

        if (data.value && data.value_end) {
            // if both values are filled
            // start/end values if end value is lower than start
            if (parseFloat(data.value_end) < parseFloat(data.value)) {
                [data.value, data.value_end] = [parseFloat(data.value_end), parseFloat(data.value)];
            }
        }

        if (!data.value && data.value_end) {
            [data.value, data.value_end] = [parseFloat(data.value_end), ''];
        }

        return data;
    },

    isUpdatable(newValue, oldValue) {
        return !tools.isEqualsLoosely(newValue, oldValue);
    },

    _formatRawValue(value) {
        const result = FrontendProductPriceFilter.__super__._formatRawValue.call(this, value);

        return {
            ...result,
            value: result.value ? result.value.toString() : '',
            value_end: result.value_end ? result.value_end.toString() : ''
        };
    },

    getTemplateData() {
        const data = FrontendProductPriceFilter.__super__.getTemplateData.call(this);

        data.showChoices = this.showChoices;
        data.currency = {
            isPrepend: localeSettings.isCurrencySymbolPrepend(),
            symbol: localeSettings.getCurrencySymbol(),
            extended: localeSettings.getCurrencySymbol().length > 1
        };

        return data;
    }
});

export default FrontendProductPriceFilter;
