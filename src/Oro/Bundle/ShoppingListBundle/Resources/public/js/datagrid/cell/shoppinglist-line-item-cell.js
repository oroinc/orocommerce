import _ from 'underscore';
import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppinglistLineItemCell = HtmlTemplateCell.extend({
    events: {
        'mouseenter': _.noop,
        'focusin': 'onFocusin',
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
        this.enterEditModeIfNeeded(e);
        this.$el.find('.line-item-container .focus-visible')
            .removeClass('focus-visible');
    }
});

export default ShoppinglistLineItemCell;
