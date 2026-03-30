import OrderLineItemDraftCreateWidget from './order-line-item-draft-create-widget';

const OrderLineItemDraftUpdateWidget = OrderLineItemDraftCreateWidget.extend({
    options: {
        ...OrderLineItemDraftCreateWidget.prototype.options,
        loadingProviderFunction: null,
        type: 'order-line-item-draft-update'
    },

    events: {
        'click [data-role="discard"]': 'onClickDiscard',
        'click [data-role="delete-line-item"]': 'onClickDelete',
        'change input:not(:hidden),input[data-draft-update-field],select,textarea': 'onChangeFormField'
    },

    constructor: function OrderLineItemDraftUpdateWidget(...args) {
        OrderLineItemDraftUpdateWidget.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        OrderLineItemDraftUpdateWidget.__super__.initialize.call(this, options);

        this.listenTo(this, 'beforeContentLoad', this.onBeforeContentLoad);
    },

    onChangeFormField() {
        this.options.row.model.set('fieldChanged', true);
    },

    loadContent(data, method, url) {
        if (this.options.loadingProviderFunction && this.firstRun) {
            this.trigger('beforeContentLoad', this);
            this.loading = this.options.loadingProviderFunction();
            this.loading
                .then(html => this._onContentLoad(html))
                .catch(err => this._onContentLoadFail(err));
            return;
        }

        return OrderLineItemDraftUpdateWidget.__super__.loadContent.call(this, data, method, url);
    },

    onBeforeContentLoad() {
        if (this.options.row && !this.saveForm && !this.firstRun) {
            this.options.row.model.set('fieldChanged', true);
        }
    },

    submitHandler(e, {isDrySubmit = false} = {}) {
        if (!isDrySubmit) {
            if (this.options.row) {
                this.options.row.model.set('fieldChanged', false);
            }

            this.saveForm = true;
        }

        OrderLineItemDraftUpdateWidget.__super__.submitHandler.call(this, e);
    },

    onClickDiscard() {
        if (this.options.row) {
            this.options.row.trigger('render:edit-mode', {
                editMode: false
            });
        }
    },

    onClickDelete() {
        this.options.row.trigger('removeItem');
    }
});

export default OrderLineItemDraftUpdateWidget;
