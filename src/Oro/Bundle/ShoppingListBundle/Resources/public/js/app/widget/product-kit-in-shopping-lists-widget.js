import FrontendDialogWidget from 'oro/dialog-widget';
import mediator from 'oroui/js/mediator';
import messenger from 'oroui/js/messenger';
import $ from 'jquery';
import _ from 'underscore';

const ProductKitInShoppingListsWidget = FrontendDialogWidget.extend({
    options: _.extend({}, FrontendDialogWidget.prototype.options, {
        preventModelRemoval: true,
        incrementalPosition: false,
        actionSectionTemplate: _.template(`
            <div data-section="<%- section %>"
                class="dialog-actions-section"></div>
        `),
        actionWrapperTemplate: _.template('<div class="action-wrapper"/>'),
        dialogOptions: {
            modal: true,
            title: null,
            resizable: false,
            width: 1200,
            minWidth: 367,
            maxWidth: 'auto',
            autoResize: true,
            dialogClass: 'stretched product-kit-dialog product-kit-in-shopping-lists-dialog'
        }
    }),

    listen: {
        'widget_dialog:open mediator': 'onWidgetDialogOpen',
        'widget_dialog:close mediator': 'onWidgetDialogClose'
    },

    SUB_DIALOG_NAME: 'product-kit-line-item-dialog',

    /**
     * @inheritdoc
     */
    constructor: function ProductKitInShoppingListWidget(options) {
        ProductKitInShoppingListWidget.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.model = this.model || options.productModel;

        options.initLayoutOptions = {
            productModel: this.model
        };

        ProductKitInShoppingListsWidget.__super__.initialize.call(this, options);
    },

    _onAdoptedFormSubmitClick: function($form, widget) {
        return ProductKitInShoppingListsWidget.__super__._onAdoptedFormSubmitClick.call(this, $form, widget);
    },

    _onContentLoadFail: function(jqxhr) {
        if (jqxhr.responseJSON) {
            return this._onJsonContentResponse(jqxhr.responseJSON);
        } else {
            return ProductKitInShoppingListsWidget.__super__._onContentLoadFail.call(this, jqxhr);
        }
    },

    _onJsonContentResponse: function(response) {
        if (response.success) {
            mediator.trigger('shopping-list:refresh');
        }

        if (response.messages) {
            Object.entries(response.messages).forEach(([type, messages]) => {
                messages.forEach(message => messenger.notificationMessage(type, message));
            });
        }

        this.remove();
    },

    onWidgetDialogOpen(dialog) {
        if (dialog.NAME === this.SUB_DIALOG_NAME) {
            const {uiDialog, overlay} = this.widget.dialog('instance');

            $(uiDialog).addClass('invisible');
            $(overlay).addClass('invisible');
        }
    },

    onWidgetDialogClose(dialog) {
        if (dialog.NAME === this.SUB_DIALOG_NAME) {
            const {uiDialog, overlay} = this.widget.dialog('instance');

            $(uiDialog).removeClass('invisible');
            $(overlay).removeClass('invisible');
        }
    },

    _bindSubmitHandler() {
        // nothing to do
    },

    getActionsElement: function() {
        if (!this.actionsEl) {
            this.actionsEl = $('<div class="form-actions widget-actions"/>').appendTo(
                this.widget.dialog('actionsContainer')
            );
        }
        return this.actionsEl;
    }
});

export default ProductKitInShoppingListsWidget;
