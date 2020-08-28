import _ from 'underscore';
import HtmlTemplateCell from 'oro/datagrid/cell/html-template-cell';

const ShoppinglistLineItemCell = HtmlTemplateCell.extend({
    events: {
        'mouseenter': _.noop,
        'focusin': 'enterEditModeIfNeeded',
        'focusout': _.noop,
        'focus': 'onFocus',
        'blur': _.noop,
        'mousedown [data-role=edit]': _.noop,
        'dblclick': _.noop
    },

    constructor: function ShoppinglistLineItemCell(options) {
        ShoppinglistLineItemCell.__super__.constructor.call(this, options);
    },

    initialize(options) {
        ShoppinglistLineItemCell.__super__.initialize.call(this, options);

        this.listenTo(this.model, 'change:unit', this.render);
    }
});

export default ShoppinglistLineItemCell;
