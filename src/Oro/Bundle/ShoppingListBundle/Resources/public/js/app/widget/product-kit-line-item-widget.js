import DialogWidget from 'oro/dialog-widget';
import mediator from 'oroui/js/mediator';
import messenger from 'oroui/js/messenger';
import _ from 'underscore';
import $ from 'jquery';

import 'jquery.validate';

const ProductKitLineItemWidget = DialogWidget.extend({
    options: _.extend({}, DialogWidget.prototype.options, {
        preventModelRemoval: true,
        incrementalPosition: false,
        dialogOptions: {
            modal: true,
            resizable: false,
            width: 890,
            minWidth: 367,
            maxWidth: 'auto',
            autoResize: true,
            dialogClass: 'stretched product-kit-dialog'
        },
        actionSectionTemplate: _.template(`
            <div data-section="<%- section %>"
                class="widget-actions-section"
                data-role="totals-section"></div>
        `)
    }),

    listen: {
        'shopping-list:line-items:before-response mediator': '_hideDialog',
        'shopping-list:line-items:update-response mediator': 'onShoppingListLineItemsChange',
        'shopping-list:line-items:error-response mediator': 'onShoppingListLineItemsChange',
        'widgetReady': 'onRenderComplete'
    },

    NAME: 'product-kit-line-item-dialog',

    /**
     * @inheritdoc
     */
    constructor: function ProductKitLineItemWidget(options) {
        ProductKitLineItemWidget.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.model = this.model || options.productModel;

        options.initLayoutOptions = {
            productModel: this.model
        };

        ProductKitLineItemWidget.__super__.initialize.call(this, options);
        this.model.set('_widget_data', this._getWidgetData(), {silent: true});
    },

    _onAdoptedFormSubmitClick($form, widget) {
        return ProductKitLineItemWidget.__super__._onAdoptedFormSubmitClick.call(this, $form, widget);
    },

    _onContentLoadFail(jqxhr) {
        if (jqxhr.responseJSON) {
            return this._onJsonContentResponse(jqxhr.responseJSON);
        } else {
            return ProductKitLineItemWidget.__super__._onContentLoadFail.call(this, jqxhr);
        }
    },

    _onJsonContentResponse(response) {
        if (response.successful) {
            mediator.trigger('shopping-list:refresh');
        }

        if (response.messages) {
            Object.entries(response.messages).forEach(([type, messages]) => {
                messages.forEach(message => messenger.notificationMessage(type, message));
            });
        }

        this.remove();
    },

    onShoppingListLineItemsChange(model, response) {
        this._showDialog();
        if (typeof response === 'string') {
            this.show();
            this._onContentLoad(response);
        } else {
            this.remove();
        }
    },

    /**
     * Listen to renderComplete event
     */
    onRenderComplete() {
        $(`#${this.widget._extraFormIdentifier}`).validate();
    },

    _renderActions() {
        ProductKitLineItemWidget.__super__._renderActions.call(this);

        const container = this.getActionsElement();

        if (container) {
            this.$('[data-role="totals"]').appendTo(this.actionsEl.find('[data-role="totals-section"]'));
            this.widget.dialog('showActionsContainer');
        }
    },

    getActionsElement() {
        if (!this.actionsEl) {
            this.actionsEl = $('<div class="form-actions widget-actions"/>').appendTo(
                this.widget.dialog('actionsContainer')
            );
            const $extraForm = this.widget.find('[data-extra-form]');
            const extraFormIdentifier = $extraForm.data('extra-form');

            $extraForm.wrap(`<form id="${extraFormIdentifier}" />`);
            this.widget._extraFormIdentifier = extraFormIdentifier;
        }
        return this.actionsEl;
    },

    _bindSubmitHandler() {
        // nothing to do
    },

    _hideDialog() {
        const {uiDialog, overlay} = this.widget.dialog('instance');

        $(uiDialog).addClass('invisible');
        $(overlay).addClass('invisible');
    },

    _showDialog() {
        const {uiDialog, overlay} = this.widget.dialog('instance');

        $(uiDialog).removeClass('invisible');
        $(overlay).removeClass('invisible');
    }
});

export default ProductKitLineItemWidget;
