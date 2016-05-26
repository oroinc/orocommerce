define(function(require) {
    'use strict';

    var Select2InputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var __ = require('orotranslation/js/translator');
    // current version: http://select2.github.io/select2/
    // last version: http://select2.github.io/examples.html
    require('jquery.select2');

    Select2InputWidget = AbstractInputWidget.extend({
        initializeOptions: {
            containerCssClass: 'oro-select2',
            dropdownCssClass: 'oro-select2__dropdown',
            placeholder: __('Please select'),
            dropdownAutoWidth: true,
            minimumResultsForSearch: Infinity // hiding the search box
        },

        widgetFunctionName: 'select2',

        destroyOptions: 'destroy',

        isInitialized: function() {
            return Boolean(this.$el.data(this.widgetFunctionName));
        },

        disposeWidget: function() {
            this.close();
            return Select2InputWidget.__super__.disposeWidget.apply(this, arguments);
        },

        findContainer: function() {
            this.$container = this.$el.data(this.widgetFunctionName).container;
        },

        open: function() {
            this.widgetFunction('open');
        },

        close: function() {
            this.widgetFunction('close');
        },

        onOpening: function(callback) {
            this._addEvent(this.widgetFunctionName + '-opening', callback);
        },

        onOpen: function(callback) {
            this._addEvent(this.widgetFunctionName + '-open', callback);
        },

        onClose: function(callback) {
            this._addEvent(this.widgetFunctionName + '-close', callback);
        },

        onSelect: function(callback) {
            this._addEvent(this.widgetFunctionName + '-selecting', callback);
        },

        onUnselect: function(callback) {
            this._addEvent(this.widgetFunctionName + '-removing', callback);
        }
    });

    return Select2InputWidget;
});
