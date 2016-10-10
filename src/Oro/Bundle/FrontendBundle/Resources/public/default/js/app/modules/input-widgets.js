define(function(require) {
    'use strict';

    var InputWidgetManager = require('oroui/js/input-widget-manager');
    var CheckboxInputWidget = require('orofrontend/default/js/app/views/input-widget/checkbox');
    var RadioInputWidget = require('orofrontend/default/js/app/views/input-widget/radio');
    var Select2InputWidget = require('oroui/js/app/views/input-widget/select2');

    InputWidgetManager.removeWidget('uniform-select');
    InputWidgetManager.removeWidget('select2');

    InputWidgetManager.addWidget('checkbox', {
        selector: 'input:checkbox',
        Widget: CheckboxInputWidget
    });

    InputWidgetManager.addWidget('radio', {
        selector: 'input:radio',
        Widget: RadioInputWidget
    });

    InputWidgetManager.addWidget('select2', {
        selector: 'select,input.select2, input.select-values-autocomplete',
        Widget: Select2InputWidget
    });
});
