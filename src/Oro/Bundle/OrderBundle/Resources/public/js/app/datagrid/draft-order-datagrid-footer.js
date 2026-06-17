import routing from 'routing';
import Footer from 'orodatagrid/js/datagrid/footer';
import OrderLineItemDraftCreateWidget from '../widget/order-line-item-draft-create-widget';
import template from 'tpl-loader!oroorder/templates/draft-create-order-footer-row.html';

const DraftOrderDataGridFooter = Footer.extend({
    template,

    renderable: true,

    constructor: function DraftOrderDataGridFooter(options) {
        DraftOrderDataGridFooter.__super__.constructor.call(this, options);
    },

    initialize(options) {
        this.gridParams = (options.metadata && options.metadata.gridParams) || {};

        const url = routing.generate('oro_order_line_item_draft_create', {
            orderId: this.gridParams.order_id,
            orderDraftSessionUuid: this.gridParams.draft_session_uuid
        });

        this.subview('draftCreateWidget', new OrderLineItemDraftCreateWidget({
            autoRender: true,
            url,
            alias: 'order-line-item-draft-create',
            elementFirst: false
        }));

        DraftOrderDataGridFooter.__super__.initialize.call(this, options);
    },

    render() {
        this.$el.append(this.template({
            colspan: this.filteredColumns.length
        }));

        this.subview('draftCreateWidget').$el.appendTo(this.$('[data-role="draft-create-container"]'));

        return this;
    }
});

export default DraftOrderDataGridFooter;
