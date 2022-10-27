import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import messenger from 'oroui/js/messenger';
import routing from 'routing';
import DialogAction from 'oro/datagrid/action/dialog-action';
import WidgetComponent from 'oroui/js/app/components/widget-component';

/**
 * Update configurable products action with matrix grid order dialog
 *
 * @export  oro/datagrid/action/update-configurable-product-action
 * @class   oro.datagrid.action.UpdateConfigurableProductAction
 * @extends oro.datagrid.action.DialogAction
 */
const UpdateConfigurableProductAction = DialogAction.extend({
    withMap: [
        [1, 620],
        [2, 760],
        [3, 900],
        [4, 1040],
        [5, 1180],
        [6, 1260]
    ],

    widgetDefaultOptions: {
        'type': 'frontend-dialog',
        'multiple': false,
        'reload-grid-name': '',
        'options': {
            simpleActionTemplate: false,
            contentElement: '.matrix-grid-update-container',
            renderActionsFromTemplate: true,
            staticPage: false,
            fullscreenMode: true,
            dialogOptions: {
                dialogClass: 'ui-dialog--frontend',
                allowMaximize: false,
                allowMinimize: false,
                modal: true,
                resizable: false,
                maximizedHeightDecreaseBy: 'minimize-bar',
                width: 800
            },
            fullscreenViewOptions: {}
        }
    },

    /**
     * @inheritdoc
     */
    constructor: function UpdateConfigurableProductAction(options) {
        UpdateConfigurableProductAction.__super__.constructor.call(this, options);
    },

    /**
     * Get suitable width for dialog depending on matrix columns count
     * @param {number} count
     * @returns {number}
     */
    getFlexibleWidth(count = 1) {
        if (count <= this.withMap[0][0]) {
            return this.withMap[0][1];
        } else if (count >= this.withMap[this.withMap.length - 1][0]) {
            return this.withMap[this.withMap.length - 1][1];
        }

        let index;
        for (let i = 1; i <= this.withMap.length - 2; i++) {
            if (count === this.withMap[i][0]) {
                index = i;
                break;
            } else if (count < this.withMap[i + 1][0]) {
                index = i;
                break;
            }
        }

        return this.withMap[index][1];
    },

    /**
     * @inheritdoc
     */
    run: function() {
        const title = __('oro.frontend.shoppinglist.matrix_grid_update.title', {
            product: this.model.attributes.name,
            shoppinglist: this.datagrid.metadata.shoppingListLabel
        });

        this.widgetOptions.options.dialogOptions.title = title;
        this.widgetOptions.options.fullscreenViewOptions.popupLabel = title;
        this.widgetOptions.options.initLayoutOptions = {
            productModel: this.model
        };
        if (!this.widgetComponent) {
            this.widgetComponent = new WidgetComponent(this.widgetOptions);
        }

        this.widgetComponent.openWidget().done(() => {
            const $form = $(this.widgetComponent.view.el).find('form');
            const columnsCount = $(this.widgetComponent.view.el).data('columns-count');

            if (columnsCount !== void 0) {
                this.widgetComponent.listenTo(this.widgetComponent.view, 'widgetReady', dialog => {
                    const width = this.getFlexibleWidth(columnsCount);

                    dialog.loadingElement.addClass('invisible');
                    dialog.widget.dialog('option', 'width', width);
                    dialog.options.dialogOptions.width = width;
                    dialog.loadingElement.removeClass('invisible');
                });
            }

            this.widgetComponent.listenTo(this.widgetComponent.view, 'adoptedFormSubmitClick', () => {
                $.ajax({
                    method: 'POST',
                    url: this.getLink(),
                    data: $form.serialize(),
                    success: response => {
                        if (response.message) {
                            messenger.notificationFlashMessage('success', response.message);
                            mediator.trigger(`datagrid:doRefresh:${this.datagrid.name}`);
                        }
                    }
                });
            });
        });
    },

    /**
     * @inheritdoc
     */
    getLink: function() {
        return routing.generate('oro_shopping_list_frontend_matrix_grid_update', {
            shoppingListId: this.datagrid.metadata.gridParams.shopping_list_id,
            productId: this.model.attributes.productId,
            unitCode: this.model.attributes.unit
        });
    }
});

export default UpdateConfigurableProductAction;

