import _ from 'underscore';
import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppinglistLineItemCell = HtmlTemplateCell.extend({
    events: {
        'mouseenter': _.noop,
        'focusin [tabindex="0"]': 'onFocusin',
        'click [tabindex="0"]': 'onClick',
        'click [data-role="label-view-mode"]': 'onClick',
        'click [data-role="radio-view-mode"]': 'enterEditModeIfNeeded',
        'change [data-role="radio-view-mode"]': 'onRadioChange',
        'focusout': _.noop,
        'blur': _.noop,
        'mousedown [data-role=edit]': _.noop,
        'dblclick': _.noop
    },

    /**
     * Determines whether use stepper buttons for quantity input
     * @property {boolean}
     */
    useInputStepper: true,

    constructor: function ShoppinglistLineItemCell(options) {
        ShoppinglistLineItemCell.__super__.constructor.call(this, options);

        this.useInputStepper = Boolean(options?.themeOptions?.useInputStepper ?? this.useInputStepper);
        this.listenTo(this.model, 'change:unitCode', this.render);
    },

    onFocusin(e) {
        if (!this.$el.is('[data-ignore-tabbable]') && e.relatedTarget) {
            _.defer(() => {
                // original focusin event's call stack has preserved
                this.enterEditModeIfNeeded(e);
            });
        }
    },

    onClick(e) {
        const relativeEl = document.querySelector(`#${e.target.getAttribute('for')}`);

        if (relativeEl) {
            relativeEl.dataset.focused = `[type="radio"][value="${relativeEl.value}"]`;
            e.target.dataset.focused = `[type="radio"][value="${relativeEl.value}"]`;
        }
        this.enterEditModeIfNeeded(e);
    },

    onRadioChange(e) {
        e.target.dataset.focused = `[type="radio"][value="${e.target.value}"]`;
        this.enterEditModeIfNeeded(e);
    },

    getTemplateData() {
        const data = ShoppinglistLineItemCell.__super__.getTemplateData.call(this);

        data.useInputStepper = this.useInputStepper;
        return data;
    }
});

export default ShoppinglistLineItemCell;
