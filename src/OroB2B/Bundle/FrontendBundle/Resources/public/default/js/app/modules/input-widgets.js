define(function(require) {
    'use strict';

    var InputWidgetManager = require('oroui/js/input-widget-manager');
    var ChosenInputWidget = require('orob2bfrontend/default/js/app/views/input-widget/chosen');

    InputWidgetManager.registerWidget({
        tagName: 'SELECT',
        Widget: ChosenInputWidget
    });
});
