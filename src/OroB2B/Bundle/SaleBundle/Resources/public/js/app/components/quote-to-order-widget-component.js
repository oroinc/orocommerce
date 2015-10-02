define(function(require) {
    'use strict';

    var QuoteToOrderWidgetComponent;
    var WidgetComponent = require('oroui/js/app/components/widget-component');
    var mediator = require('oroui/js/mediator');

    QuoteToOrderWidgetComponent = WidgetComponent.extend({
        defaults: {
            type: 'dialog',
            createOnEvent: 'click',
            options: {
                loadingMaskEnabled: true,
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    width: 1000,
                    height: 500,
                    allowMaximize: true,
                    dblclick: 'maximize'
                }
            }
        },

        _bindEnvironmentEvent: function(widget) {
            QuoteToOrderWidgetComponent.__super__._bindEnvironmentEvent.call(this, widget);

            this.listenTo(widget, 'formSave', function(redirectUrl) {
                widget.remove();
                if (redirectUrl) {
                    mediator.execute('redirectTo', {url: redirectUrl});
                } else {
                    mediator.execute('refreshPage');
                }
            });
        }
    });

    return QuoteToOrderWidgetComponent;
});
