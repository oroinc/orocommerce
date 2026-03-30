import {delay} from 'underscore';
import tools from 'oroui/js/tools';
import Row from 'orodatagrid/js/datagrid/row';
import OrderLineItemDraftUpdateWidget from '../../widget/order-line-item-draft-update-widget';
import editOrderTemplate from 'tpl-loader!oroorder/templates/draft-edit-order-row.html';
import ActionColumn from 'orodatagrid/js/datagrid/column/action-column';

const DraftRow = Row.extend({
    editOrderTemplate,

    className: 'row-transition',

    /**
     * @inheritdoc
     */
    listen: {
        'render:edit-mode': 'switchToEditMode',
        'change:editMode model': 'onChangeEditMode',
        'removeItem': 'onRemoveItem'
    },

    /**
     * @inheritdoc
     */
    constructor: function DraftRow(options) {
        DraftRow.__super__.constructor.call(this, options);
    },

    render() {
        delay(() => {
            this.$el.css('--placeholder-size', this.$el.css('height'));
        });

        this.$el.empty();

        DraftRow.__super__.render.call(this);

        this.$el.toggleClass('grid-row-line-item-edit', this.model.get('editMode'));
        this.$el.toggleClass('grid-row-line-item-view', !this.model.get('editMode'));
    },

    onRowUpdatedStatusChange(...args) {
        DraftRow.__super__.onRowUpdatedStatusChange.apply(this, args);

        if (this.model.get('isUpdated')) {
            this.keepRowInView();
        }
    },

    keepRowInView() {
        if (this.disposed) {
            return;
        }

        tools.elementScrollIntoViewIfNeeded(this.el);
    },

    renderAllItems() {
        DraftRow.__super__.renderAllItems.call(this);

        if (this.model.get('editMode')) {
            this.$el.html(this.editOrderTemplate());

            const plugin = this.dataCollection?.batchContentProvider;
            const loadingProviderFunction = plugin
                ? () => plugin.requestContent(this.model.id)
                : null;

            return this.subview('widget', new OrderLineItemDraftUpdateWidget({
                autoRender: true,
                container: this.$('[data-role="container"]'),
                loadingElement: this.$('[data-role="container"]'),
                row: this,
                url: this.model.get('oro_order_line_item_draft_update'),
                _widgetContainer: 'order-line-item-draft-update',
                loadingProviderFunction
            }));
        }

        return this;
    },

    /**
     * Switches a row into edit mode.
     * @param {Object} params
     * @param {string} params.content HTML markup containing editable row data.
     */
    switchToEditMode({editMode, ...options}) {
        this.model.set({
            editMode,
            widgetOptions: options
        });
    },

    onChangeEditMode(model, editMode) {
        // Forse rerender grid row
        if (editMode) {
            for (const itemKey of Object.keys(this.getItemViews())) {
                if (!(this.subview(`itemView:${itemKey}`).column instanceof ActionColumn)) {
                    this.removeSubview(`itemView:${itemKey}`);
                }
            }
        } else {
            for (const itemKey of Object.keys(this.getItemViews())) {
                this.removeSubview(`itemView:${itemKey}`);
            }
        }

        this.render();
        setTimeout(() => this.keepRowInView());
    },

    onRemoveItem() {
        const deleteAction = this.model.get('availableActions')
            .find(({configuration}) => configuration.name === 'delete');

        if (deleteAction) {
            return deleteAction.execute();
        }
    }
});

export default DraftRow;
