import _ from 'underscore';
import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import UnitsUtil from 'oroproduct/js/app/units-util';
import QuantityHelper from 'oroproduct/js/app/quantity-helper';
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
        display_name: '[data-name="field__product"]',
        sku: '[data-name="field__sku"]',
        unit: '[data-name="field__unit"]',
        quantity: '[data-name="field__quantity"]'
    },

    listen() {
        return {
            'change:product_name model': () => this.updateControlValue('display_name'),
            'change:sku model': () => this.updateControlValue('sku', 'display_name'),
            'change:quantity model': () => this.updateControlValue('quantity'),
            'change:product_units model': () => this.updateUnitsSelector(),
            'removed model': 'onModelRemoved',
            'change model': 'updateUI',
            'change:errors model': 'showErrors'
        };
    },

    events() {
        return {
            'keyup': 'updateRemoveRowButton',
            'change': 'updateRemoveRowButton',
            [`keyup ${this.attrElem.quantity}`]: 'onQuantityChange',
            [`change ${this.attrElem.unit}`]: 'onUnitChange',
            [`change ${this.attrElem.display_name}`]: 'onDisplayNameChange',
            [`productFound.autocomplete ${this.attrElem.display_name}`]: 'onProductChange',
            [`productNotFound.autocomplete ${this.attrElem.display_name}`]: 'onProductNotFound',
            'validate-element': 'onAttrElementValidate'
        };
    },

    constructor: function QuickAddRowView(options) {
        this.attrElem = Object.assign({}, this.attrElem, options.selectors || {});
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
        // row views are initialized in reverse order (all components in a layout initialized in such way)
        const index = productsCollection.findLastIndex(model => !model.has('index'));
        if (index !== -1) {
            // there is vacant model in collection
            this.model = productsCollection.models[index];
            this.model.set({index: this.getRowNumber()});
        } else {
            // or new model
            this.model = productsCollection.add({index: this.getRowNumber()});
        }
        this.updateUnitsSelector(true);
        this._writeDOMValues(true);
        this.showErrors();
    },

    initUnitValidator() {
        this.$(this.attrElem.unit).data('unitValidator', {
            isValid: () => this.model.isValidUnit(),
            getMessage: () => {
                const unitName = this.model.get('unit') || this.model.get('unit_label');
                return __(this.unitErrorText, {unit: _.escape(unitName), sku: _.escape(this.model.get('sku'))});
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
                value = QuantityHelper.getQuantityNumberOrDefaultValue(value, null);
                break;
        }
        return value;
    },

    _readDOMValues() {
        const entries = Object.keys(this.attrElem)
            .map(attr => [attr, this._readDOMValue(attr)]);
        return Object.fromEntries(entries);
    },

    _writeDOMValue(attr, value, silent) {
        const $input = this.$(this.attrElem[attr]);
        const inputValue = this._readDOMValue(attr);
        switch (attr) {
            case 'quantity':
                value = QuantityHelper.formatQuantity(value, $input.data('precision'), true);
                break;
        }
        if (value !== inputValue) {
            $input.val(value);
            if (!silent) {
                $input.change();
            }
        }
    },

    _writeDOMValues(silent = false) {
        Object.keys(this.attrElem)
            .forEach(attr => this._writeDOMValue(attr, this.model.get(attr), silent));
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

    updateQuantityPrecision(unit, silent = false) {
        const precision = this.model.get('product_units')[unit];
        const $quantity = this.$(this.attrElem.quantity);
        if ($quantity.data('precision') !== precision) {
            $quantity
                .data('precision', precision)
                .inputWidget('refresh');
            // in case quantity was not written due to incompatible precision, do it again
            this._writeDOMValue('quantity', this.model.get('quantity'), silent);
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

        this.model.clear();
        this.model.set(attrs);
    },

    updateUnitsSelector(silent = false) {
        UnitsUtil.updateSelect(this.model, this.$(this.attrElem.unit), silent);
        if (silent) {
            // in case it's silent unit selector update, we need to update precision manually
            this.updateQuantityPrecision(this.model.get('unit'), silent);
        }
    },

    updateUI() {
        if (!this.model.isValidUnit()) {
            this.$el.closest('form').validate().element(this.$(this.attrElem.unit)[0]);
        }
        this.updateRemoveRowButton();
    },

    updateRemoveRowButton() {
        const $inputs = this.$(Object.values(this.attrElem).join(','));
        const enabled = $.makeArray($inputs).some(input => $(input).val());
        this.$(this.elem.remove).toggleClass('hidden', !enabled);
    },

    showErrors() {
        const errors = this.model.get('errors');
        if (errors && errors.length) {
            const namePrefix = this.$el.closest('[data-content]').data('content');
            const validator = this.$el.closest('form').data('validator');
            const errorEntries = errors.map(({propertyPath, message}) => {
                return [propertyPath, {errors: Array.isArray(message) ? message : [message]}];
            });
            validator.showBackendErrors(Object.fromEntries(errorEntries), namePrefix);
        }
    },

    /**
     * Handle validation event and remove errors related to the attribute from the model
     * @param event
     */
    onAttrElementValidate(event) {
        if (event.invalid) {
            return;
        }

        // valid attribute name
        const [attr] = Object.entries(this.attrElem)
            .find(([, selector]) => this.$(event.target).is(selector));

        if (attr) {
            // remove error for valid attribute
            const errors = this.model.get('errors')
                .filter(error => error.propertyPath !== attr);
            this.model.set('errors', errors);
        }
    },

    onModelRemoved() {
        this.$(this.elem.remove).click();
    }
});

export default QuickAddRowView;
