import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';

const ProductUnitQuantityView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'unitSelector', 'quantitySelector'
    ]),

    /**
     * @property string
     */
    unitSelector: '[data-role="unit"]',

    /**
     * @property string
     */
    quantitySelector: '[data-role="quantity"]',

    /**
     * @inheritdoc
     */
    events() {
        const events = {};

        events[`change ${this.unitSelector}`] = this.onChange;

        return events;
    },

    /**
     * @inheritdoc
     */
    constructor: function ProductUnitQuantityView(options) {
        ProductUnitQuantityView.__super__.constructor.call(this, options);
    },

    /**
     * @param {Object} e
     */
    onChange(e) {
        this._actualizePrecision();
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        ProductUnitQuantityView.__super__.initialize.call(this, options);
        this._actualizePrecision();
    },

    /**
     * Actualizes unit precision if exists
     * @param {jQuery.Element} [$unitElement]
     * @private
     */
    _actualizePrecision($unitElement = this.$(this.unitSelector)) {
        const unit = $unitElement.val();
        const precisions = $unitElement.data('unit-precisions') || {};

        if (unit in precisions) {
            $(this.$(this.quantitySelector)).data('precision', precisions[unit]).inputWidget('refresh');
        }
    }
});

export default ProductUnitQuantityView;
