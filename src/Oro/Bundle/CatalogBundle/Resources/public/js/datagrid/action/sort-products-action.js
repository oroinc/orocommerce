import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import __ from 'orotranslation/js/translator';
import AbstractAction from 'oro/datagrid/action/abstract-action';
import dialogActionsTemplate from 'tpl-loader!orocatalog/templates/sort-products-dialog-actions.html';

const SortProductsAction = AbstractAction.extend({
    /** @property {string} */
    sortOrderUID: undefined,

    /** @property {string} */
    categoryTitle: undefined,

    /** @property {number} */
    categoryId: undefined,

    /** @property {boolean} */
    autoExecute: false,

    messages: {
        confirm_title: 'oro.catalog.sort_products.confirm.title',
        confirm_content: 'oro.catalog.sort_products.confirm.content',
        confirm_ok: 'oro.catalog.sort_products.confirm.ok',
        confirm_cancel: 'oro.catalog.sort_products.confirm.cancel'
    },

    constructor: function SortProductsAction(options) {
        Object.defineProperty(this, 'confirmation', {
            get() {
                return this.isPageStateChanged();
            }
        });

        SortProductsAction.__super__.constructor.call(this, options);
    },

    initialize(options) {
        this.launcherOptions = {
            label: __('oro.catalog.datagrid.action.sort_products.label'),
            ariaLabel: __('oro.catalog.datagrid.action.sort_products.aria_label', {category: this.categoryTitle}),
            className: 'btn sort-mode-action',
            iconClassName: 'fa-list-ol',
            launcherMode: 'icon-only'
        };

        this.frontend_handle = 'dialog';
        this.frontend_options = {
            title: __('oro.catalog.sort_products.dialog.title', {category: this.categoryTitle}),
            dialogOptions: {
                limitTo: '#container',
                state: 'maximized',
                modal: true
            }
        };
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
        return !this.categoryId || mediator.execute('isPageStateChanged');
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
        const $from = $(this.formSelector);
        const actionUrl = new URL($from[0].action);
        const actionSearchParams = new URLSearchParams(actionUrl.search);
        actionSearchParams.append('manage_sort_order', this.sortOrderUID);
        actionUrl.search = String(actionSearchParams);
        $from[0].action = String(actionUrl);

        $from.submit();
    },

    async _handleWidget() {
        this.frontend_options.actionsEl = $(dialogActionsTemplate());

        const widget = await SortProductsAction.__super__._handleWidget.call(this);
        Promise.all([widget.loading, widget.deferredRender]).then(() => {
            mediator.execute('showMessage', 'warning',
                __('oro.catalog.sort_products.dialog.waning'),
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
