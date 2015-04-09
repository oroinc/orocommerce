/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var RequestChangeStatusWidgetComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        widgetManager = require('oroui/js/widget-manager'),
        mediator = require('oroui/js/mediator'),
        messenger = require('oroui/js/messenger');

    RequestChangeStatusWidgetComponent = BaseComponent.extend({
        initialize: function (options) {
            widgetManager.getWidgetInstance(options.wid, function (widget) {
                messenger.notificationFlashMessage('success', options.successMessage);
                mediator.trigger('widget_success:' + widget.getAlias());
                mediator.trigger('widget_success:' + widget.getWid());
                widget.remove();
            });
        }
    });

    return RequestChangeStatusWidgetComponent;
});
