import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import UnitsUtil from 'oroproduct/js/app/units-util';
import QuantityHelper from 'oroproduct/js/app/quantity-helper';
import __ from 'orotranslation/js/translator';
import errorMessageTemplate from 'tpl-loader!oroform/templates/error-template.html';
import warningMessageTemplate from 'tpl-loader!oroform/templates/warning-template.html';

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
        product: '[data-name="field__product"]',
        sku: '[data-name="field__sku"]',
        unit: '[data-name="field__unit"]',
        quantity: '[data-name="field__quantity"]'
    },

    listen() {
        return {
            'change:product_name model': () => this.updateControlValue('product'),
            'change:sku model': () => this.updateControlValue('sku', 'product'),
            'change:quantity model': () => this.updateControlValue('quantity'),
            'change:product_units model': () => this.updateUnitsSelector(),
            'removed model': 'onModelRemoved',
            'change model': 'updateUI',
            'change:errors model': 'onErrorsChange',
            'change:warnings model': 'onWarningsChange'
        };
    },

    events() {
        return {
            'keyup': 'updateRemoveRowButton',
            'change': 'updateRemoveRowButton',
            [`keyup ${this.attrElem.quantity}`]: 'onQuantityChange',
            [`change ${this.attrElem.unit}`]: 'onUnitChange',
            [`change ${this.attrElem.product}`]: 'onProductChange',
            [`productFound.autocomplete ${this.attrElem.product}`]: 'onProductFound',
            [`productNotFound.autocomplete ${this.attrElem.product}`]: 'onProductNotFound',
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
        this.$el.parent().data('model', this.model);
        this.updateUnitsSelector(true);
        this._writeDOMValues(true);
        this.updateRemoveRowButton();
        this.showErrors();
        this.showWarnings();
    },

    getRowNumber() {
        return Number(this.$el.closest('[data-role="row"]').attr('data-content').match(/\[(\d+)]$/)[1]);
    },

    getProductDisplayName() {
        const sku = this.model.get('sku');
        const productName = this.model.get('product_name');

        return productName
            ? `${sku} - ${productName}`
            : sku;
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

    _writeDOMValue(attr, silent) {
        const $input = this.$(this.attrElem[attr]);
        const inputValue = this._readDOMValue(attr);
        let value = null;
        switch (attr) {
            case 'product':
                value = this.getProductDisplayName();
                break;
            case 'quantity':
                value = QuantityHelper.formatQuantity(this.model.get(attr), $input.data('precision'), true);
                break;
            default:
                value = this.model.get(attr);
                break;
        }
        if (value !== inputValue) {
            $input.val(value);
            if (!silent) {
                $input.trigger('change');
            }
        }
    },

    _writeDOMValues(silent = false) {
        Object.keys(this.attrElem).forEach(attr => this._writeDOMValue(attr, silent));
    },

    updateControlValue(...attrs) {
        attrs.forEach(attr => this._writeDOMValue(attr));
    },

    onQuantityChange() {
        const quantity = this._readDOMValue('quantity');
        this.model.set({
            quantity,
            quantity_changed_manually: true
        });

        this.model.collection.validateModels([this.model]);
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
        if (this.model.previous('unit') !== this.model.get('unit')) {
            if (this.model.isValidUnit()) {
                this.removeErrorForAttribute('unit');
            } else {
                const errors = [...this.model.get('errors')];
                const unitName = this.model.get('unit') || this.model.get('unit_label');
                errors.push({
                    propertyPath: 'unit',
                    message: __(this.unitErrorText, {unit: _.escape(unitName), sku: _.escape(this.model.get('sku'))})
                });
                this.model.set('errors', errors);
            }
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
            this._writeDOMValue('quantity', silent);
        }
    },

    onProductChange() {
        const value = this._readDOMValue('product');
        if (value === '' && this.model.get('sku') !== '') {
            this.model.clear();
        }
    },

    onProductFound(event) {
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
        this.model.collection.validateModels([this.model]);
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
        this.updateRemoveRowButton();
    },

    updateRemoveRowButton() {
        const {index, ...restAttrs} = this.model.toBackendJSON();
        const enabled = Object.values(restAttrs).some(value => Boolean(value));
        this.$(this.elem.remove).toggleClass('disabled', !enabled);
    },

    showErrors() {
        const errors = this.model.get('errors');
        if (!errors || !errors.length) {
            return;
        }
        const $rowErrorsContainer = this.$el.parent().find('.fields-row-error');
        errors.map(({propertyPath, message}) => {
            const $input = this.$(this.attrElem[propertyPath]);
            $input.addClass(`${$input[0].tagName.toLowerCase()}--error`);
            const id = $input.attr('id');
            $rowErrorsContainer
                .append(`<span id="${id}-error" class="validation-failed">${errorMessageTemplate({message})}</span>`);
        });
    },

    showWarnings() {
        const warnings = this.model.get('warnings');
        if (!warnings || !warnings.length) {
            return;
        }
        const $rowWarningsContainer = this.$el.parent().find('.fields-row-warning');
        warnings.map(({propertyPath, message}) => {
            const $input = this.$(this.attrElem[propertyPath]);
            $input.addClass(`${$input[0].tagName.toLowerCase()}--warning`);
            const id = $input.attr('id');
            $rowWarningsContainer.append(
                `<span id="${id}-warning" class="validation-warning">${warningMessageTemplate({message})}</span>`
            );
        });
    },

    onErrorsChange() {
        const previousErrors = this.model.previous('errors');
        previousErrors.map(({propertyPath}) => {
            const $input = this.$(this.attrElem[propertyPath]);
            $input.removeClass(`${$input[0].tagName.toLowerCase()}--error`);
            const id = $input.attr('id');
            this.$el.parent().find(`#${id}-error`).remove();
        });
        this.showErrors();
    },

    onWarningsChange() {
        const previousWarnings = this.model.previous('warnings');
        previousWarnings.map(({propertyPath}) => {
            const $input = this.$(this.attrElem[propertyPath]);
            $input.removeClass(`${$input[0].tagName.toLowerCase()}--warning`);
            const id = $input.attr('id');
            this.$el.parent().find(`#${id}-warning`).remove();
        });
        this.showWarnings();
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
            this.removeErrorForAttribute(attr);
        }
    },

    removeErrorForAttribute(attr) {
        const errors = this.model.get('errors')
            .filter(error => error.propertyPath !== attr);
        this.model.set('errors', errors);
    },

    onModelRemoved() {
        this.$(this.elem.remove).trigger('click');
    }
});

export default QuickAddRowView;
