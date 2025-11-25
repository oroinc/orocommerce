import _ from 'underscore';
import __ from 'orotranslation/js/translator';
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

    defaultGridThemeOptions: {
        selectVariantButtonClass: 'btn btn--flat btn-select-variants',
        singleUnitMode: false,
        singleUnitModeCodeVisible: false
    },

    constructor: function ShoppinglistLineItemCell(options) {
        this.gridThemeOptions = _.defaults(
            _.pick(options.themeOptions, Object.keys(this.defaultGridThemeOptions)),
            this.defaultGridThemeOptions
        );

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
        return {
            ...ShoppinglistLineItemCell.__super__.getTemplateData.call(this),
            useInputStepper: this.useInputStepper,
            gridThemeOptions: this.gridThemeOptions
        };
    },

    render() {
        ShoppinglistLineItemCell.__super__.render.call(this);

        if (this.model.get('isConfigurable') && !this.model.get('quantity')) {
            this.createConfigurableAction();
        }

        return this;
    },

    createConfigurableAction() {
        const actionsColumn = this.column.collection.find(model => model.get('actions'));
        const UpdateConfigurableAction = actionsColumn.get('actions').update_configurable;

        if (typeof UpdateConfigurableAction === 'function') {
            this.updateConfigurableAction = new UpdateConfigurableAction({
                model: this.model,
                datagrid: actionsColumn.get('datagrid')
            });

            const launcher = this.updateConfigurableAction.createLauncher({
                className: this.gridThemeOptions.selectVariantButtonClass,
                label: __('oro.frontend.shoppinglist.actions.update_configurable_line_item.select_variant_label')
            });

            launcher.render().$el.appendTo(this.getCellRootElement());
        }
    },

    /**
     * @returns {jQuery.Element}
     */
    getCellRootElement() {
        return this.$('[data-role="cell-quantity-root"]').length
            ? this.$('[data-role="cell-quantity-root"]')
            : this.$el;
    }
});

export default ShoppinglistLineItemCell;
