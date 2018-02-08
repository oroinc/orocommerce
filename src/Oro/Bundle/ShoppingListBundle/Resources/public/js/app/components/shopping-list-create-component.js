define(
    [
        'oroui/js/widget-manager',
        'oroui/js/messenger',
        'oroui/js/mediator',
        'orotranslation/js/translator',
        'underscore'
    ],
    function(widgetManager, messenger, mediator, __, _) {
        'use strict';

        return function(options) {
            if (options.savedId) {
                widgetManager.getWidgetInstance(
                    options._wid,
                    function(widget) {
                        if (!options.message) {
                            options.message = __('oro_frontend.widget_form_component.save_flash_success');
                        }

                        _.each(options.messages, function(message) {
                            messenger.notificationFlashMessage('success', message);
                        }, this);

                        mediator.trigger('widget_success:' + widget.getAlias(), options);
                        mediator.trigger('widget_success:' + widget.getWid(), options);
                        widget.trigger('formSave', {
                            savedId: options.savedId,
                            shoppingListCreateEnabled: options.shoppingListCreateEnabled
                        });
                        if (!widget.disposed) {
                            widget.remove();
                        }
                    }
                );
            }
        };
    }
);
