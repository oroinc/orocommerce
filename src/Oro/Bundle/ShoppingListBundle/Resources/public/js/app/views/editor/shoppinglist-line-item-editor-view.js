import _ from 'underscore';
import $ from 'jquery';
import TextEditorView from 'oroform/js/app/views/editor/text-editor-view';
import NumberFormatter from 'orofilter/js/formatter/number-formatter';
import NumberFormat from 'orolocale/js/formatter/number';
import template from 'tpl-loader!oroshoppinglist/templates/editor/shoppinglist-line-item-editor.html';

const ShoppinglistLineItemEditorView = TextEditorView.extend({
    events: {
        'input input[name="quantity"]': 'onValueChange',
        'change input[name="quantity"]': 'onValueChange',
        'change [name="unitCode"]': 'onUnitValueChange',
        'mousedown .toggle-container': function(e) {
            // Do not close an editor after clicking on unit element
            this._preventCancelEditing = true;
        }
    },

    template,

    /**
     * Determines whether use stepper buttons for quantity input
     * @property {boolean}
     */
    useInputStepper: true,

    defaultGridThemeOptions: {
        singleUnitMode: false,
        singleUnitModeCodeVisible: false
    },

    constructor: function ShoppinglistLineItemEditorView(...args) {
        ShoppinglistLineItemEditorView.__super__.constructor.apply(this, args);
    },

    preinitialize(options) {
        this.useInputStepper = Boolean(options?.themeOptions?.useInputStepper ?? this.useInputStepper);
    },

    initialize(options) {
        this.gridThemeOptions = _.defaults(
            _.pick(options.themeOptions, Object.keys(this.defaultGridThemeOptions)),
            this.defaultGridThemeOptions
        );

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

        delete this._preventCancelEditing;
        this.model.isSyncedWithEditor(true);
        ShoppinglistLineItemEditorView.__super__.dispose.call(this);
    },

    getTemplateData() {
        return {
            gridThemeOptions: this.gridThemeOptions,
            useInputStepper: this.useInputStepper,
            data: this.model.toJSON()
        };
    },

    focus(event) {
        const focused = event.target.getAttribute('data-focused');
        const $input = this.$('input[name="quantity"]');

        if (focused) {
            if (focused === '.select2-container') {
                this.$(focused).select2('open');
            } else if (this.$(focused).is(':radio')) {
                this.$(focused).trigger('focus').attr('checked', 'checked').trigger('change');
            } else if (this.$(focused).is('.input-quantity-btn')) {
                this.$(focused).trigger('focus').trigger('click');
            }

            if (this.$el.data('validator')) {
                this.$el.valid();
            }

            return;
        }

        if (typeof this.options.cursorOffset === 'number' && !Number.isNaN(this.options.cursorOffset)) {
            $input.setCursorPosition(this.options.cursorOffset);
        } else {
            $input.setCursorToEnd();
        }
        $input.trigger('focus');
    },

    isChanged() {
        const isChanged = _.some(Object.entries(this.getValue()), ([key, value]) => {
            return this.model.get(key) !== value;
        });

        this.model.isSyncedWithEditor(!isChanged);

        return isChanged;
    },

    onFocusout(event) {
        if (this._preventCancelEditing) {
            delete this._preventCancelEditing;
            return;
        }

        const select2 = this.$('[name="unitCode"]').data('select2');

        if (
            !this.isChanged() &&
            !$.contains(this.el, event.relatedTarget) &&
            (!select2 || !select2.opened())
        ) {
            // original focusout event's call stack has preserved
            setTimeout(() => this.trigger('cancelAction'));
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
        const units = this.updateUnitList(this.getUnitCode());
        const precision = Object.values(units).find(unit => unit.selected).precision;
        this.$el.find('input[name="quantity"]')
            .data('precision', precision)
            .inputWidget('refresh');
    },

    getValue: function() {
        return {
            quantity: parseFloat(NumberFormat.unformatStrict(this.$('input[name="quantity"]').val())),
            unitCode: this.getUnitCode()
        };
    },

    getUnitCode() {
        let $el = this.$('[name="unitCode"]');

        if ($el.is(':radio')) {
            $el = $el.filter(':checked');
        }

        if ($el.length) {
            return $el.val();
        }

        return this.model.get('unitCode');
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
