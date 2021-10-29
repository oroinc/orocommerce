import _ from 'underscore';
import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppinglistLineItemCell = HtmlTemplateCell.extend({
    events: {
        'mouseenter': _.noop,
        'focusin [tabindex="0"]': 'onFocusin',
        'click [tabindex="0"]': 'onClick',
        'focusout': _.noop,
        'focus': 'onFocus',
        'blur': _.noop,
        'mousedown [data-role=edit]': _.noop,
        'dblclick': _.noop
    },

    constructor: function ShoppinglistLineItemCell(options) {
        ShoppinglistLineItemCell.__super__.constructor.call(this, options);

        this.listenTo(this.model, 'change:unitCode', this.render);
    },

    onFocusin(e) {
        if (!this.$el.is('[data-ignore-tabbable]')) {
            _.defer(() => {
                // original focusin event's call stack has preserved
                this.enterEditModeIfNeeded(e);
            });
        }
    },

    onClick(e) {
        this.enterEditModeIfNeeded(e);
    }
});

export default ShoppinglistLineItemCell;
