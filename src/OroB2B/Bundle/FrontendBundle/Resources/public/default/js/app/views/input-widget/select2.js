define(function(require) {
    'use strict';

    var Select2InputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var _ = require('underscore');
    // current version: http://select2.github.io/select2/
    // last version: http://select2.github.io/examples.html
    require('jquery.select2');

    Select2InputWidget = AbstractInputWidget.extend({
        initializeOptions: {
            containerCssClass: 'oro-select2-container',
            dropdownCssClass: 'oro-select2-dropdown',
            minimumResultsForSearch: Infinity // hiding the search box

        },

        widgetFunctionName: 'select2',

        destroyOptions: 'destroy',

        refreshOptions: 'update',

        refreshOnChange: true,

        dispose: function() {
            AbstractInputWidget.__super__.dispose.apply(this, arguments);
            this.close();
            this.$el('destroy');
        },

        isInitialized: function() {
            return this.$el.data(this.widgetFunctionName) ? true : false;
        },

        findContainer: function() {
            this.$container = this.$el.data(this.widgetFunctionName).container;
        },

        getSelectetValue: function() {
            return this.$el[this.widgetFunctionName]('val');
        },

        setValue: function(value) {
            this.$el[this.widgetFunctionName]('val', value);
        },

        clear: function() {
            this.$el[this.widgetFunctionName]('val', '');
        },

        addEvent: function(eventName, callback) {
            var self = this;
            this.$el.on(eventName, function() {
                if (_.isFunction(callback)) {
                    callback.apply(self, arguments);
                }
            });
        },

        opening: function(callback) {
            this.addEvent(this.widgetFunctionName + '-opening', callback);
        },

        open: function(callback) {
            this.addEvent(this.widgetFunctionName + '-open', callback);
        },

        close: function(callback) {
            this.addEvent(this.widgetFunctionName + '-close', callback);
        },

        select: function(callback) {
            this.addEvent(this.widgetFunctionName + '-selecting', callback);
        },

        unselect: function(callback) {
            this.addEvent(this.widgetFunctionName + '-removing', callback);
        }
    });

    return Select2InputWidget;
});
