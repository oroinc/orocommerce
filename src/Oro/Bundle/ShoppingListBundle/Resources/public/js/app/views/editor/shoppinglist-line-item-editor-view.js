import _ from 'underscore';
import $ from 'jquery';
import TextEditorView from 'oroform/js/app/views/editor/text-editor-view';
const NumberFormatter = require('orofilter/js/formatter/number-formatter');
import NumberFormat from 'orolocale/js/formatter/number';

const ShoppinglistLineItemEditorView = TextEditorView.extend({
    events: {
        'input input[name="quantity"]': 'onValueChange',
        'change select[name="unitCode"]': 'onUnitValueChange'
    },

    template: require('tpl-loader!oroshoppinglist/templates/editor/shoppinglist-line-item-editor.html'),

    constructor: function ShoppinglistLineItemEditorView(...args) {
        ShoppinglistLineItemEditorView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.formatter = new NumberFormatter(options);
        this.updateUnitList(this.model.get('unit'));
        ShoppinglistLineItemEditorView.__super__.initialize.call(this, options);
        this.updateRangeValidationRule();
    },

    updateRangeValidationRule() {
        if (this.validationRules.Range) {
            this.validationRules.Range = {
                ...this.validationRules.Range,
                min: this.model.getMinimumQuantity(),
                max: this.model.getMaximumQuantity()
            };
        }
    },

    render() {
        if (this.options.quantity) {
            this.setFormState(this.options.quantity);
        }

        ShoppinglistLineItemEditorView.__super__.render.call(this);

        this.validator.settings.rules = {
            quantity: $.validator.filterUnsupportedValidators(this.getValidationRules())
        };
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.model.isSyncedWithEditor(true);
        ShoppinglistLineItemEditorView.__super__.dispose.call(this);
    },

    getTemplateData() {
        return {
            data: this.model.toJSON()
        };
    },

    focus(event) {
        const focused = event.target.getAttribute('data-focused');
        if (focused) {
            if (focused === '.select2-container') {
                this.$el.find(focused).select2('open');
            }
            return;
        }
        this.$('input[name="quantity"]').setCursorToEnd().focus();
    },

    isChanged() {
        const isChanged = _.some(Object.entries(this.getValue()), ([key, value]) => {
            return this.model.get(key) !== value;
        });

        this.model.isSyncedWithEditor(!isChanged);

        return isChanged;
    },

    onFocusout(event) {
        const select2 = this.$('select[name="unitCode"]').data('select2');

        if (
            !this.isChanged() &&
            !$.contains(this.el, event.relatedTarget) &&
            !select2.opened()
        ) {
            // original focusout event's call stack has preserved
            _.defer(() => {
                this.trigger('cancelAction');
            });
        }
    },

    onValueChange() {
        this.updateSubmitButtonState();
        this.trigger('change');
    },

    onUnitValueChange(event) {
        this.onValueChange(event);
        this.updateUnitPrecision();
    },

    updateUnitPrecision() {
        const units = this.updateUnitList(this.$('select[name="unitCode"]').val());
        const precision = Object.values(units).find(unit => unit.selected).precision;
        this.$el.find('input[name="quantity"]')
            .data('precision', precision)
            .inputWidget('refresh');
    },

    getValue: function() {
        return {
            quantity: parseFloat(NumberFormat.unformatStrict(this.$('input[name="quantity"]').val())),
            unitCode: this.$('select[name="unitCode"]').val()
        };
    },

    updateUnitList(currentUnit) {
        return _.mapObject(this.model.get('units'), (unit, key) => {
            unit.selected = key === currentUnit;
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
            units: this.updateUnitList(value.unitCode)
        };
    }
});

export default ShoppinglistLineItemEditorView;
