define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'oro/datagrid/action/dialog-action',
    'oroui/js/app/components/widget-component'
], function($, _, __, messenger, DialogAction, WidgetComponent) {
    'use strict';

    /**
     * Update configurable products action with matrix grid order dialog
     *
     * @export  oro/datagrid/action/update-configurable-product-action
     * @class   oro.datagrid.action.UpdateConfigurableProductAction
     * @extends oro.datagrid.action.DialogAction
     */
    const UpdateConfigurableProductAction = DialogAction.extend({
        widgetDefaultOptions: {
            'type': 'frontend-dialog',
            'multiple': false,
            'reload-grid-name': '',
            'options': {
                simpleActionTemplate: false,
                contentElement: '.matrix-grid-update-container',
                renderActionsFromTemplate: true,
                staticPage: false,
                fullscreenMode: false,
                dialogOptions: {
                    allowMaximize: false,
                    allowMinimize: false,
                    modal: true,
                    resizable: false,
                    maximizedHeightDecreaseBy: 'minimize-bar',
                    width: 800
                }
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function UpdateConfigurableProductAction(options) {
            UpdateConfigurableProductAction.__super__.constructor.call(this, options);
        },
        /**
         * @inheritDoc
         */
        run: function() {
            this.widgetOptions.options.dialogOptions.title = __('oro.frontend.shoppinglist.matrix_grid_update.title', {
                product: this.model.attributes.name,
                shoppinglist: this.datagrid.metadata.shoppingListLabel
            });

            this.widgetOptions.options.initLayoutOptions = {
                productModel: this.model
            };
            if (!this.widgetComponent) {
                this.widgetComponent = new WidgetComponent(this.widgetOptions);
            }

            this.widgetComponent.openWidget().done(_.bind(function() {
                const $form = $(this.widgetComponent.view.el).find('form');

                this.widgetComponent.listenTo(this.widgetComponent.view, 'adoptedFormSubmitClick', _.bind(function() {
                    $.ajax({
                        method: 'POST',
                        url: this.model.attributes.update_configurable_link,
                        data: $form.serialize(),
                        success: function(response) {
                            if (response.message) {
                                messenger.notificationFlashMessage('success', response.message);
                            }
                        }
                    });
                }, this));
            }, this));
        }
    });

    return UpdateConfigurableProductAction;
});
