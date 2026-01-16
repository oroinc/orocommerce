import {debounce} from 'underscore';
import mediator from 'oroui/js/mediator';
import FrontendDialogWidget from 'oro/dialog-widget';
import messenger from 'oroui/js/messenger';
import $ from 'jquery';

const ShoppingListErrorsModalWidget = FrontendDialogWidget.extend({
    options: {
        ...FrontendDialogWidget.prototype.options,
        dialogOptions: {
            modal: true,
            resizable: false,
            width: 768,
            minWidth: 367,
            maxWidth: 'auto',
            autoResize: true,
            dialogClass: 'stretched shopping-list-errors-modal'
        }
    },

    /**
     * @inheritdoc
     */
    constructor: function ShoppingListErrorsModalWidget(options) {
        this.invalidIds = [];
        ShoppingListErrorsModalWidget.__super__.constructor.call(this, options);
    },

    initialize(options) {
        if (options.title) {
            this.options.dialogOptions.title = options.title;
        }

        this.invalidIds = options.widgetData?.invalidIds;

        ShoppingListErrorsModalWidget.__super__.initialize.call(this, options);

        this.listenTo(mediator, `datagrid-${options.gridName}:rendered`, grid => {
            this.showMessage('error', options.errorValidationMessages);

            this.listenTo(mediator, `${options.gridName}:updated`, data => {
                if (data.metadata?.invalidIds) {
                    grid.metadata.invalidIds = data.metadata.invalidIds;
                    this.invalidIds = grid.metadata.invalidIds;
                }
            });

            this.listenTo(grid.collection, 'reset remove', debounce(() => {
                if (grid.metadata?.invalidIds) {
                    this.invalidIds = grid.metadata.invalidIds;
                }

                if (grid.collection.length === 0) {
                    this.showMessage('success', options.successValidationMessages);
                    this.updateActionLabel();
                }
            }));
        });
    },

    showMessage(type, message) {
        const opts = {
            namespace: `shopping-list-errors-${this.cid}`,
            hideCloseButton: true
        };

        if (message.description) {
            opts.description = message.description;
        }

        if (message.message) {
            message = message.message;
        }

        messenger.notificationMessage(type, message, opts);
    },

    updateActionLabel() {
        this.getAction('form_submit', 'adopted', action => {
            return action.text(this.options.successProceedBtnLabel);
        });

        if (this.footerElement.length) {
            this.footerElement.remove();
        }
    },

    _onAdoptedFormSubmitClick() {
        const data = {
            invalidIds: this.invalidIds,
            triggered_by: this.options.widgetData.triggered_by,
            action: this.options.widgetData.action,
            render: false
        };

        mediator.execute('showLoading');

        $.ajax({
            method: 'get',
            url: this.options.url,
            data: data,
            dataType: 'json',
            success: response => {
                if (response.success && response.redirectUrl) {
                    mediator.execute('redirectTo', {url: response.redirectUrl}, {redirect: true});
                }

                if (!response.success && response.message) {
                    mediator.execute('hideLoading');
                    mediator.execute('showFlashMessage', 'error', response.message);
                }

                this.remove();
            }
        });
    },

    _renderActions() {
        ShoppingListErrorsModalWidget.__super__._renderActions.call(this);

        const container = this.getActionsElement();

        if (container && container.length) {
            this.footerElement = this.$('[data-role="footer-element"]');
            container.after(this.footerElement);
        }
    },

    removeMessageContainer() {
        messenger.clear(`shopping-list-errors-${this.cid}`);
        ShoppingListErrorsModalWidget.__super__.removeMessageContainer.call(this);
    }
});

export default ShoppingListErrorsModalWidget;
