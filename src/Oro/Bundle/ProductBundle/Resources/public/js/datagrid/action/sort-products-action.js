import $ from 'jquery';
import Backbone from 'backbone';
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
        this.warnings = {
            save_warning: 'oro.product.sort_products.dialog.save_warning',
            limit_warning: 'oro.product.sort_products.dialog.limit_warning',
            ...this.warnings
        };

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
            desktopLoadingBar: true,
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
            className: 'modal oro-modal-normal',
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
        const $form = this.datagrid.$el.closest('form');

        const actionInput = $form.find('input[name="input_action"]');
        actionInput.val(this.inputAction);

        $form.submit();
    },

    async _handleWidget() {
        const eventBus = Object.create(Backbone.Events);
        let datagrid;
        this.listenToOnce(eventBus, 'init', plugin => datagrid = plugin.main);

        this.frontend_options.actionsEl = $(dialogActionsTemplate());
        this.frontend_options.initLayoutOptions = {
            gridBuildersOptions: {
                sortRowsDragNDropBuilder: {
                    eventBus
                }
            }
        };

        const dialog = await SortProductsAction.__super__._handleWidget.call(this);
        Promise.all([dialog.loading, dialog.deferredRender]).then(() => {
            mediator.execute('showMessage', 'warning',
                __(this.warnings.save_warning),
                {
                    dismissible: false,
                    showIcon: false,
                    animation: false,
                    container: dialog.$messengerContainer
                });

            const {totalRecords, pageSize} = datagrid.collection.state;
            if (totalRecords > pageSize) {
                mediator.execute('showMessage', 'warning',
                    __(this.warnings.limit_warning, {limit: pageSize}, pageSize),
                    {
                        dismissible: false,
                        showIcon: false,
                        animation: false,
                        container: dialog.$messengerContainer
                    });
            }

            const sequenceOfChanges = [];
            // collect all changes that is done in sortOrder dialog
            this.listenTo(eventBus, 'saveChanges', (xhr, data) => sequenceOfChanges.push(data));

            this.listenToOnce(dialog, 'close', () => {
                this.onSortingComplete(sequenceOfChanges);
                dialog.widget.off(this.eventNamespace());
                this.stopListening(eventBus);
            });
        });

        return dialog;
    },

    /**
     * Handles sorting action completion, the handler can be overloaded through options
     * @param {Array<{sortOrder: Object, removeProducts: Array<string>}>} sequenceOfChanges
     */
    onSortingComplete(sequenceOfChanges) {
        // do nothing for now,
        // the handler can be overloaded through the options
    }
});

export default SortProductsAction;
