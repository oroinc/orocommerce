import _ from 'underscore';
import $ from 'jquery';
import TextEditorView from 'oroform/js/app/views/editor/text-editor-view';
const NumberFormatter = require('orofilter/js/formatter/number-formatter');

const ShoppinglistLineItemEditorView = TextEditorView.extend({
    events: {
        'input input[name="quantity"]': 'onValueChange',
        'change select[name="unit"]': 'onValueChange'
    },

    template: require('tpl-loader!oroshoppinglist/templates/editor/shoppinglist-line-item-editor.html'),

    constructor: function ShoppinglistLineItemEditorView(...args) {
        ShoppinglistLineItemEditorView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.formatter = new NumberFormatter(options);
        ShoppinglistLineItemEditorView.__super__.initialize.call(this, options);
    },

    render() {
        if (this.options.quantity) {
            this.setFormState(this.formatter.toRaw(this.options.quantity));
        }

        ShoppinglistLineItemEditorView.__super__.render.call(this);

        this.validator.settings.rules = {
            quantity: $.validator.filterUnsupportedValidators(this.getValidationRules())
        };
    },

    getTemplateData() {
        return {
            data: this.model.toJSON()
        };
    },

    focus() {
        this.$('input[name="quantity"]').setCursorToEnd().focus();
    },

    isChanged() {
        const res = _.some(Object.entries(this.getValue()), ([key, value]) => {
            return this.model.get(key) !== value;
        });

        this.model.set('_state', res);

        return res;
    },

    onFocusout() {},

    onValueChange() {
        this.updateSubmitButtonState();
        this.trigger('change');
    },

    getValue: function() {
        return {
            quantity: parseFloat(this.$('input[name="quantity"]').val()),
            unit: this.$('select[name="unit"]').val()
        };
    },

    updateUnitList(currentUnit) {
        return _.mapObject(this.model.get('units'), unit => {
            unit.selected = unit.label === currentUnit;
            return unit;
        });
    },

    getServerUpdateData() {
        return {
            id: this.model.get('id'),
            ...this.getValue()
        };
    },

    getModelUpdateData() {
        const value = this.getValue();

        return {
            ...value,
            units: this.updateUnitList(value.unit)
        };
    }
});

export default ShoppinglistLineItemEditorView;
