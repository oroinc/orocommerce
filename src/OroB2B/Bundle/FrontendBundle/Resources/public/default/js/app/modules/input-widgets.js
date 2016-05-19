define(function(require) {
    'use strict';

    var InputWidgetManager = require('oroui/js/input-widget-manager');
    var Select2InputWidget = require('orob2bfrontend/default/js/app/views/input-widget/select2');
    var CheckboxInputWidget = require('orob2bfrontend/default/js/app/views/input-widget/checkbox');
    var RadioInputWidget = require('orob2bfrontend/default/js/app/views/input-widget/radio');

    InputWidgetManager.removeWidget('uniform-select');
    InputWidgetManager.addWidget('select2', {
        selector: 'select:not([data-not-select2])',
        Widget: Select2InputWidget
    });

    InputWidgetManager.addWidget('checkbox', {
        selector: 'input:checkbox',
        Widget: CheckboxInputWidget
    });

    InputWidgetManager.addWidget('radio', {
        selector: 'input:radio',
        Widget: RadioInputWidget
    });
});
