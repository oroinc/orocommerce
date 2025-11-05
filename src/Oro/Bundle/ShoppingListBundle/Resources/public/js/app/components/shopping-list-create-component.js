import widgetManager from 'oroui/js/widget-manager';
import messenger from 'oroui/js/messenger';
import mediator from 'oroui/js/mediator';
import __ from 'orotranslation/js/translator';
import _ from 'underscore';

export default function(options) {
    if (options.savedId) {
        widgetManager.getWidgetInstance(
            options._wid,
            function(widget) {
                if (_.isEmpty(options.messages)) {
                    options.messages = [__('oro_frontend.widget_form_component.save_flash_success')];
                }

                options.messages.forEach((message, index) => {
                    const messageOptions = index === 0 ? {namespace: 'shopping_list'} : {};
                    messenger.notificationFlashMessage('success', message, messageOptions);
                });

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
