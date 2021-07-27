import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import UnitsUtil from 'oroproduct/js/app/units-util';
import QuantityHelper from 'oroproduct/js/app/quantity-helper';
import InputWidgetManager from 'oroui/js/input-widget-manager';
import __ from 'orotranslation/js/translator';

const QuickAddRowView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'defaultQuantity', 'unitErrorText'
    ]),

    defaultQuantity: 1,
    unitErrorText: 'oro.product.validation.unit.invalid',

    elem: {
        remove: '[data-role="row-remove"]'
    },

    attrElem: {
        display_name: '[data-name="field__product-display-name"]',
        sku: '[data-name="field__product-sku"]',
        quantity: '[data-name="field__product-quantity"]',
        unit: '[data-name="field__product-unit"]'
    },

    listen() {
        return {
            'change:product_name model': () => this.updateControlValue('display_name'),
            'change:sku model': () => this.updateControlValue('sku', 'display_name'),
            'change:quantity model': () => this.updateControlValue('quantity'),
            'change:product_units model': 'updateUnitsSelector',
            'removed model': 'onModelRemoved',
            'change model': 'updateUI'
        };
    },

    events() {
        return {
            [`keyup ${this.attrElem.quantity}`]: 'onQuantityChange',
            [`change ${this.attrElem.unit}`]: 'onUnitChange',
            [`change ${this.attrElem.display_name}`]: 'onDisplayNameChange',
            [`productFound.autocomplete ${this.attrElem.display_name}`]: 'onProductChange',
            [`productNotFound.autocomplete ${this.attrElem.display_name}`]: 'onProductNotFound'
        };
    },

    constructor: function QuickAddRowView(options) {
        QuickAddRowView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        const {productsCollection} = options;
        if (!productsCollection) {
            throw new Error('Option `productsCollection` is require for QuickAddRowView');
        }

        this.initModel(productsCollection, options);
        this.initUnitValidator();
        this.$(this.attrElem.unit).removeClass('disabled');
        QuickAddRowView.__super__.initialize.call(this, options);
    },

    dispose() {
        if (this.disposed) {
            return;
        }
        this.stopListening(this.model);
        // in the dispose method the model will be removed from collection silently,
        // but we need events to get triggered
        if (this.model.collection) {
            this.model.collection.remove(this.model);
        }
        this.model.dispose();
        this.$(this.attrElem.unit).removeData('unitValidator');
        return QuickAddRowView.__super__.dispose.call(this);
    },

    /**
     * Initializes model from a form's row data (if there are values),
     * or takes a vacant model from collection (if there is such),
     * or create new one
     *
     * @param productsCollection
     * @param options
     */
    initModel(productsCollection, options) {
        const attrs = this._readDOMValues();

        if (attrs.sku) {
            // there are data in a form's row
            this.model = productsCollection.add({...attrs});
        } else {
            // (rows are initialized in reverse order, all components in a layout initialized in such way)
            const index = productsCollection.findLastIndex(model => !model.has('_order'));
            // there is vacant model in collection or new model
            this.model = index !== -1 && productsCollection.models[index] || productsCollection.push({});
            this.updateUnitsSelector();
            this._writeDOMValues();
        }

        // define row # in model
        this.model.set({_order: this.getRowNumber()});
    },

    initUnitValidator() {
        this.$(this.attrElem.unit).data('unitValidator', {
            isValid: () => this.model.isValidUnit(),
            getMessage: () => {
                const unitName = this.model.get('unit') || this.model.previous('unit_label');
                return __(this.unitErrorText, {unit: _.escape(unitName)});
            }
        });
    },

    getRowNumber() {
        return Number(this.$el.closest('[data-role="row"]').attr('data-content').match(/\[(\d+)]$/)[1]);
    },

    _readDOMValue(attr) {
        let value = this.$(this.attrElem[attr]).val();
        switch (attr) {
            case 'quantity':
                value = QuantityHelper.getQuantityNumberOrDefaultValue(value, NaN);
                break;
        }
        return value;
    },

    _readDOMValues() {
        const entries = Object.keys(this.attrElem)
            .map(attr => [attr, this._readDOMValue(attr)]);
        return Object.fromEntries(entries);
    },

    _writeDOMValue(attr, value) {
        const $input = this.$(this.attrElem[attr]);
        switch (attr) {
            case 'quantity':
                value = QuantityHelper.formatQuantity(value, $input.data('precision'), true);
                break;
        }
        if (value !== $input.val()) {
            $input.val(value).change();
            if (InputWidgetManager.hasWidget($input)) {
                $input.inputWidget('refresh');
            }
        }
    },

    _writeDOMValues() {
        Object.keys(this.attrElem)
            .forEach(attr => this._writeDOMValue(attr, this.model.get(attr)));
    },

    updateControlValue(...attrs) {
        attrs.forEach(attr => this._writeDOMValue(attr, this.model.get(attr)));
    },

    onQuantityChange() {
        const quantity = this._readDOMValue('quantity');
        this.model.set({
            quantity,
            quantity_changed_manually: true
        });
    },

    onUnitChange() {
        const unit = this._readDOMValue('unit');
        this.updateQuantityPrecision(unit); // always try to update precision
        if (unit !== this.model.get('unit')) {
            this.model.set({
                unit,
                unit_label: UnitsUtil.getUnitsLabel(this.model)[unit]
            });
        }
    },

    updateQuantityPrecision(unit) {
        const precision = this.model.get('product_units')[unit];
        const $quantity = this.$(this.attrElem.quantity);
        if ($quantity.data('precision') !== precision) {
            $quantity
                .data('precision', precision)
                .inputWidget('refresh');
            this.updateControlValue('quantity'); // in case it was not written due to incompatible precision
        }
    },

    onDisplayNameChange() {
        const value = this._readDOMValue('display_name');
        if (value === '' && this.model.get('sku') !== '') {
            this.model.clear();
        }
    },

    onProductChange(event) {
        const {id, sku, units, quantity, 'defaultName.string': productName, ...extraAttrs} = event.item;
        const attrs = {
            sku,
            product_name: productName,
            quantity: quantity || this.model.get('quantity') || this.defaultQuantity,
            units_loaded: typeof units !== 'undefined',
            product_units: {...units},
            ...extraAttrs
        };
        this.model.set(attrs);
    },

    onProductNotFound(event) {
        if (this.disposed) {
            return;
        }
        const {id, sku, ...extraAttrs} = event.item;
        const attrs = {
            sku,
            ...extraAttrs
        };

        this.model.set(attrs);
    },

    updateUnitsSelector() {
        UnitsUtil.updateSelect(this.model, this.$(this.attrElem.unit));
    },

    updateUI() {
        if (!this.model.isValidUnit()) {
            this.$el.closest('form').validate().element(this.$(this.attrElem.unit)[0]);
        }
        this.$(this.elem.remove).toggle(Boolean(this.model.get('sku')));
    },

    onModelRemoved() {
        this.$(this.elem.remove).click();
    }
});

export default QuickAddRowView;
