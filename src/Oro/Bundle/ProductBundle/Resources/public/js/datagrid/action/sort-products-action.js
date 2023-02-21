import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import __ from 'orotranslation/js/translator';
import AbstractAction from 'oro/datagrid/action/abstract-action';
import dialogActionsTemplate from 'tpl-loader!oroproduct/templates/sort-products-dialog-actions.html';

const SortProductsAction = AbstractAction.extend({
    /** @property {string} */
    inputAction: undefined,

    /** @property {number} */
    entityId: undefined,

    /** @property {boolean} */
    autoExecute: false,

    constructor: function SortProductsAction(options) {
        Object.defineProperty(this, 'confirmation', {
            get() {
                return this.isPageStateChanged();
            }
        });

        SortProductsAction.__super__.constructor.call(this, options);
    },

    initialize(options) {
        this.messages = {
            confirm_title: 'oro.product.sort_products.confirm.title',
            confirm_content: 'oro.product.sort_products.confirm.content',
            confirm_ok: 'oro.product.sort_products.confirm.ok',
            confirm_cancel: 'oro.product.sort_products.confirm.cancel',
            ...this.messages
        };

        this.launcherOptions = {
            label: __('oro.product.grid.action.sort_products.label'),
            ariaLabel: __('oro.product.grid.action.sort_products.aria_label'),
            className: 'btn sort-mode-action',
            iconClassName: 'fa-list-ol',
            launcherMode: 'icon-only',
            ...this.launcherOptions
        };

        this.frontend_handle = 'dialog';
        this.frontend_options = $.extend(true, {
            title: __('oro.product.sort_products.dialog.title'),
            dialogOptions: {
                limitTo: '#container',
                state: 'maximized',
                modal: true
            }
        }, this.frontend_options);

        SortProductsAction.__super__.initialize.call(this, options);
    },

    delegateListeners() {
        SortProductsAction.__super__.delegateListeners.call(this);
        if (this.autoExecute) {
            this.listenToOnce(mediator, 'page:afterChange', this._handleWidget.bind(this));
        }
    },

    isPageStateChanged() {
        // there's no active category or there are unsaved changes
        return !this.entityId || mediator.execute('isPageStateChanged');
    },

    getConfirmDialogOptions() {
        const options = {
            ...SortProductsAction.__super__.getConfirmDialogOptions.call(this),
            className: 'modal oro-modal-danger',
            attributes: {
                role: 'alertdialog'
            }
        };

        return options;
    },

    executeConfiguredAction() {
        if (this.isPageStateChanged()) {
            this._saveFormWithScheduledAction();
        } else {
            SortProductsAction.__super__.executeConfiguredAction.call(this);
        }
    },

    _saveFormWithScheduledAction() {
        const $from = this.datagrid.$el.closest('form');

        // const actionInput = $form.find('input[name="input_action"]');
        // actionInput.val(this.inputAction);

        $from.submit();
    },

    async _handleWidget() {
        this.frontend_options.actionsEl = $(dialogActionsTemplate());

        const widget = await SortProductsAction.__super__._handleWidget.call(this);
        Promise.all([widget.loading, widget.deferredRender]).then(() => {
            mediator.execute('showMessage', 'warning',
                __('oro.product.sort_products.dialog.waning'),
                {
                    dismissible: false,
                    showIcon: false,
                    animation: false,
                    container: widget.$messengerContainer
                });
        });
        return widget;
    }
});

export default SortProductsAction;
