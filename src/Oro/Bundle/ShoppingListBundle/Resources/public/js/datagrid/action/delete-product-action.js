import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import messenger from 'oroui/js/messenger';
import routing from 'routing';
import DeleteAction from 'oro/datagrid/action/delete-action';

/**
 * Delete action with confirm dialog, triggers REST DELETE request
 *
 * @export  oro/datagrid/action/delete-product-action
 * @class   oro.datagrid.action.DeleteProductAction
 * @extends oro.datagrid.action.DeleteAction
 */
const DeleteProductAction = DeleteAction.extend({
    /**
     * @inheritdoc
     */
    constructor: function DeleteProductAction(options) {
        DeleteProductAction.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    getConfirmDialogOptions() {
        return {
            title: this.getConfirmContentTitle(),
            content: this.getConfirmContentMessage(),
            okText: __(this.messages.confirm_ok),
            cancelText: __(this.messages.confirm_cancel)
        };
    },

    /**
     * Get confirm content title
     *
     * @return {String}
     */
    getConfirmContentTitle() {
        return __(
            this.messages.confirm_title,
            _.mapObject(this.model.toJSON(), value => _.isString(value) ? _.escape(value) : value)
        );
    },

    toggleLoadingOverlay() {
        if (typeof this.model.subModels === 'function') {
            for (const subModel of this.model.subModels()) {
                if (typeof subModel.toggleLoadingOverlay === 'function') {
                    subModel.toggleLoadingOverlay(true);
                }
            }
        }

        if (typeof this.model.toggleLoadingOverlay === 'function') {
            this.model.toggleLoadingOverlay(true);
        }
    },

    /**
     * @inheritdoc
     */
    doDelete() {
        const success = __(this.messages.success,
            _.mapObject(this.model.toJSON(), value => _.isString(value) ? _.escape(value) : value)
        );
        const $datagridEl = this.datagrid.$el;

        $datagridEl.trigger('ajaxStart');
        this.toggleLoadingOverlay();

        if (this.request) {
            this.request.abort();
        }

        const datagridThemeOptions = this.datagrid.themeOptions || {};

        this.request = this.model.destroy({
            url: this.getLink(),
            wait: true,
            reset: false,
            uniqueOnly: true,
            toggleLoading: false,
            // to prevent main application loading bar from been shown
            global: this.configuration.showGlobalLoadingBar ?? true,
            success: (model, response, options) => {
                messenger.notificationFlashMessage('success', success, {namespace: 'shopping_list'});
                let eventName = 'shopping-list:refresh';

                if (datagridThemeOptions?.shoppingListEditItemPrefixEventName) {
                    eventName = `${datagridThemeOptions.shoppingListEditItemPrefixEventName}:delete`;
                }
                mediator.trigger(eventName, options, model);
            },
            complete: () => {
                $datagridEl.trigger('ajaxComplete');
            }
        });
    },

    /**
     * @inheritdoc
     */
    getLink: function() {
        if (this.model.attributes.isConfigurable) {
            return routing.generate('oro_api_shopping_list_frontend_delete_line_item_configurable', {
                shoppingListId: this.datagrid.metadata.gridParams.shopping_list_id,
                productId: this.model.attributes.productId,
                unitCode: this.model.attributes.unit,
                savedForLaterGrid: this.model.collection.options?.savedForLaterGrid ?? false
            });
        } else {
            return routing.generate('oro_api_shopping_list_frontend_delete_line_item', {
                id: this.model.attributes.id,
                onlyCurrent: this.model.attributes.variantId || this.model.attributes.isKit ? 1 : 0
            });
        }
    }
});

export default DeleteProductAction;
