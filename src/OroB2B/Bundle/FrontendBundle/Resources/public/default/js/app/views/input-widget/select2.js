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
            containerCssClass: 'oro-select2',
            dropdownCssClass: 'oro-select2__dropdown',
            placeholder: 'Select an Option',
            minimumResultsForSearch: Infinity // hiding the search box
        },

        refreshOptions: '',

        widgetFunctionName: 'select2',

        destroyOptions: 'destroy',

        refreshOnChange: true,

        destroy: function() {
            this.closeSelect();
            this.$el[this.widgetFunctionName]('destroy');
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

        selOptions: function(obj) {
            var options = this.$el.data(this.widgetFunctionName).opts;

            _.each(obj, function(item, index) {
                options[index] = item;
            });
        },

        setValue: function(value) {
            this.$el[this.widgetFunctionName]('val', value);
        },

        clear: function() {
            this.$el[this.widgetFunctionName]('val', '');
        },

        openSelect: function() {
            this.$el[this.widgetFunctionName]('open');
        },

        closeSelect: function() {
            this.$el[this.widgetFunctionName]('close');
        },

        focusSelect: function() {
            this.$el[this.widgetFunctionName]('focus');
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
