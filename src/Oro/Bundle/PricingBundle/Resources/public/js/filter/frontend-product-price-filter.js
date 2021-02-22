import ProductPriceFilter from 'oro/filter/product-price-filter';
import template from 'tpl-loader!oropricing/templates/product/pricing-range-filter.html';
import unitTemplate from 'tpl-loader!oropricing/templates/product/pricing-range-units-filter.html';
import localeSettings from 'orolocale/js/locale-settings';
import tools from 'oroui/js/tools';
import error from 'oroui/js/error';

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

    /**
     * @extends typeValues
     */
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

    /**
     * Enable/disable show filter criteria selector
     * @property {boolean}
     */
    showChoices: true,

    /**
     * Enabled single unit mode
     * @property {boolean}
     */
    singleUnitMode: false,

    /**
     * Default unit
     * @property {string}
     */
    defaultUnitCode: null,

    /**
     * @constructor
     * @inheritDoc
     */
    constructor: function FrontendProductPriceFilter(options) {
        if (this.singleUnitMode && !this.defaultUnitCode) {
            error.showErrorInConsole(
                `'defaultUnitCode' property should be defined when 'singleUnitMode' property is enabled`
            );
        }

        FrontendProductPriceFilter.__super__.constructor.call(this, options);
    },

    /**
     * @override swapValues
     * @param data
     * @returns {Object}
     */
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

        return data;
    },

    /**
     * @override
     * @param newValue
     * @param oldValue
     * @returns {boolean}
     */
    isUpdatable(newValue, oldValue) {
        return !tools.isEqualsLoosely(newValue, oldValue);
    },

    /**
     * @extends _formatRawValue
     * @param value
     * @returns {Object}
     * @private
     */
    _formatRawValue(value) {
        const result = FrontendProductPriceFilter.__super__._formatRawValue.call(this, value);

        return {
            ...result,
            unit: this.singleUnitMode ? this.defaultUnitCode : result.unit,
            value: result.value ? result.value.toString() : '',
            value_end: result.value_end ? result.value_end.toString() : ''
        };
    },

    /**
     * @extends getTemplateData
     * @returns {Object}
     */
    getTemplateData() {
        return {
            ...FrontendProductPriceFilter.__super__.getTemplateData.call(this),
            showChoices: this.showChoices,
            currency: {
                isPrepend: localeSettings.isCurrencySymbolPrepend(),
                symbol: localeSettings.getCurrencySymbol(),
                extended: localeSettings.getCurrencySymbol().length > 1
            }
        };
    },

    /**
     * @extends getUnitTemplateData
     * @returns {Object}
     */
    getUnitTemplateData() {
        return {
            ...FrontendProductPriceFilter.__super__.getUnitTemplateData.call(this),
            singleUnitMode: this.singleUnitMode,
            defaultUnitCode: this.defaultUnitCode
        };
    }
});

export default FrontendProductPriceFilter;
