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
                        messenger.notificationFlashMessage('success', __('orob2b.shoppinglist.line_item_save.flash.success'));
                        mediator.trigger('widget_success:' + widget.getAlias());
                        mediator.trigger('widget_success:' + widget.getWid());
                        widget.trigger('formSave', options.savedId);
                        widget.remove();
                    }
                );
            }
        };
    }
);
