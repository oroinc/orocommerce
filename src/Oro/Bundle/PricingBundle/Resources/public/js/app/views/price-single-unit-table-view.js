import {sortBy} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!oropricing/templates/price-single-unit-table.html';

import localeSettings from 'orolocale/js/locale-settings';
import numeral from 'numeral';

const PriceSingleUnitTableView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'prices', 'showCurrencySymbol'
    ]),

    /**
     * @inheritdoc
     */
    autoRender: true,

    /**
     * @inheritdoc
     */
    template,

    /**
     * @inheritdoc
     */
    constructor: function PriceSingleUnitTableView(options) {
        PriceSingleUnitTableView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        if (options.prices === void 0) {
            throw Error('Option "prices" cannot be null');
        }

        if (options.productModel === void 0) {
            throw Error('Option "productModel" cannot be null');
        }

        this.prices = options.prices;
        this.unit = options.productModel.get('unit');

        this.listenTo(options.productModel, 'change:unit', (model, newUnit) => {
            this.unit = newUnit;
            this.render();
        });

        PriceSingleUnitTableView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     */
    getTemplateData: function() {
        const data = PriceSingleUnitTableView.__super__.getTemplateData.call(this);

        data.localeSettings = localeSettings;
        data.numeral = numeral;
        data.unit = this.unit;
        data.showCurrencySymbol = this.showCurrencySymbol;
        data.prices = sortBy(this.prices.filter(price => price.unit === this.unit), 'quantity');
        return data;
    },


    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        delete this.unit;
        delete this.prices;

        PriceSingleUnitTableView.__super__.dispose.call(this);
    }
});

export default PriceSingleUnitTableView;
