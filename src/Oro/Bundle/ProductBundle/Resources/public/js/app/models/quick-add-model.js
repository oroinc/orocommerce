import _ from 'underscore';
import BaseModel from 'oroui/js/app/models/base/model';
import UnitsUtil from 'oroproduct/js/app/units-util';
import __ from 'orotranslation/js/translator';

const QuickAddModel = BaseModel.extend({
    defaults: function() {
        return {
            _order: void 0,

            sku: '',
            product_name: '',
            quantity: null,
            unit: '',

            product_units: {},
            units_loaded: false,
            quantity_changed_manually: false,

            unit_label: null,
            unit_placeholder: __('oro.product.frontend.quick_add.form.unit.default')
        };
    },

    constructor: function QuickAddModel(attributes, options) {
        QuickAddModel.__super__.constructor.call(this, attributes, options);
    },

    initialize(attributes, options) {
        this.listenTo(this, {
            'change:unit': this.onUnitChange,
            'change:product_units': this.onUnitsLoaded
        });
        QuickAddModel.__super__.initialize.call(this, attributes, options);
    },

    /**
     * Extends get methods to have getter functions for calculable attributes
     *
     * @param attr
     * @return {*}
     */
    get(attr) {
        if (typeof this[`get_${attr}`] == 'function') {
            return this[`get_${attr}`]();
        }

        return QuickAddModel.__super__.get.call(this, attr);
    },

    /**
     * Getter for `display_name` attribute
     *
     * @return {string|*}
     */
    get_display_name() {
        const sku = this.get('sku');
        const productName = this.get('product_name');

        return productName ? `${sku} - ${productName}` : sku;
    },

    onUnitsLoaded() {
        if (!this.get('unit') && this.get('unit_label')) {
            const unit = this._resolveUnitCode(this.get('unit_label'));
            if (unit) {
                this.set('unit', unit);
            }
        }
    },

    onUnitChange() {
        const unitLabel = this.get('unit') ? UnitsUtil.getUnitLabel(this, this.get('unit')) : null;
        this.set('unit_label', unitLabel);
    },

    /**
     * Gets valid unit code by unit label case insensitively.
     *
     * @param {String|undefined} unitLabel
     * @returns {String|undefined}
     * @private
     */
    _resolveUnitCode(unitLabel) {
        if (typeof unitLabel === 'string') {
            unitLabel = unitLabel.toLowerCase();
        }

        const labels = UnitsUtil.getUnitsLabel(this);
        return _.findKey(labels, label => label.toLowerCase() === unitLabel);
    },

    clear() {
        const {_order, ...defaults} = _.result(this, 'defaults');
        this.set(defaults);
    },

    isValidUnit: function() {
        return !this.get('units_loaded') || _.has(this.get('product_units'), this.get('unit'));
    }
});

export default QuickAddModel;
