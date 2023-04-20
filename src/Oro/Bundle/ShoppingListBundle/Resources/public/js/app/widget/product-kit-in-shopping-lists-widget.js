import FrontendDialogWidget from 'orofrontend/js/app/components/frontend-dialog-widget';
import mediator from 'oroui/js/mediator';
import messenger from 'oroui/js/messenger';
import _ from 'underscore';

const ProductKitInShoppingListsWidget = FrontendDialogWidget.extend({
    options: _.extend({}, FrontendDialogWidget.prototype.options, {
        preventModelRemoval: true,
        incrementalPosition: false,
        desktopLoadingBar: true,
        dialogOptions: {
            modal: true,
            title: null,
            resizable: false,
            width: 890,
            minWidth: 367,
            maxWidth: 'auto',
            autoResize: true,
            dialogClass: 'product-kit-in-shopping-lists-dialog'
        }
    }),

    fullscreenViewOptions: {
        dialogClass: 'product-kit-in-shopping-lists-dialog'
    },

    listen: {
        'shopping-list:line-items:before-response mediator': 'remove'
    },

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

    _bindSubmitHandler() {
        // nothing to do
    }
});

export default ProductKitInShoppingListsWidget;
