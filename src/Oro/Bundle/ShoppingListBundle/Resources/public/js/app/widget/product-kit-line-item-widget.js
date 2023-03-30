import FrontendDialogWidget from 'orofrontend/js/app/components/frontend-dialog-widget';
import mediator from 'oroui/js/mediator';
import messenger from 'oroui/js/messenger';
import _ from 'underscore';

const ProductKitLineItemWidget = FrontendDialogWidget.extend({
    options: _.extend({}, FrontendDialogWidget.prototype.options, {
        preventModelRemoval: true,
        incrementalPosition: false,
        dialogOptions: {
            modal: true,
            title: null,
            resizable: false,
            width: 'auto',
            autoResize: true,
            dialogClass: 'product-kit-line-item-widget-dialog'
        }
    }),

    /**
     * @inheritdoc
     */
    constructor: function ProductKitLineItemWidget(options) {
        ProductKitLineItemWidget.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.model = this.model || options.productModel;

        options.initLayoutOptions = {
            productModel: this.model
        };

        ProductKitLineItemWidget.__super__.initialize.call(this, options);
    },

    _onAdoptedFormSubmitClick: function($form, widget) {
        return ProductKitLineItemWidget.__super__._onAdoptedFormSubmitClick.call(this, $form, widget);
    },

    _onContentLoadFail: function(jqxhr) {
        if (jqxhr.responseJSON) {
            return this._onJsonContentResponse(jqxhr.responseJSON);
        } else {
            return ProductKitLineItemWidget.__super__._onContentLoadFail.call(this, jqxhr);
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
    }
});

export default ProductKitLineItemWidget;
