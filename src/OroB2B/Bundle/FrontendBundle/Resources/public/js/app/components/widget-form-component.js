/*jslint nomen:true*/
/*global define*/
define(
    ['oroui/js/widget-manager', 'oroui/js/messenger', 'oroui/js/mediator', 'orotranslation/js/translator'],
    function (widgetManager, messenger, mediator, __) {
        'use strict';

        return function (options) {
            if (options.savedId) {
                widgetManager.getWidgetInstance(
                    options._wid,
                    function (widget) {
                        if (!options.message) {
                            options.message = 'orob2b_frontend.widget_form_component.save_flash_success'
                        }

                        messenger.notificationFlashMessage('success', __(options.message));
                        mediator.trigger('widget_success:' + widget.getAlias(), options);
                        mediator.trigger('widget_success:' + widget.getWid(), options);
                        widget.trigger('formSave', options.savedId);
                        widget.remove();
                    }
                );
            }
        };
    }
);
