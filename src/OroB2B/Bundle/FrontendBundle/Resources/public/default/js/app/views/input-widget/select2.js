define(function(require) {
    'use strict';

    var Select2InputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var $ = require('jquery');
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
            minimumInputLength: 0,
            minimumResultsForSearch: 7,
            adaptContainerCssClass: function() {
                return false;
            }
        },

        widgetFunctionName: 'select2',

        destroyOptions: 'destroy',

        initialize: function(options) {
            //fix select2.each2 bug, when empty string is FALSE
            this.$el.attr('class', $.trim(this.$el.attr('class')));
            return Select2InputWidget.__super__.initialize.apply(this, arguments);
        },

        isInitialized: function() {
            return Boolean(this.$el.data(this.widgetFunctionName));
        },

        disposeWidget: function() {
            this.close();
            return Select2InputWidget.__super__.disposeWidget.apply(this, arguments);
        },

        findContainer: function() {
            return this.$el.data(this.widgetFunctionName).container;
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
        },

        val: function() {
            Array.prototype.unshift.call(arguments, 'val');
            return this.widgetFunction.apply(this, arguments);
        },

        valData: function() {
            return this.widgetFunction('data');
        },

        updatePosition: function() {
            return this.widgetFunction('positionDropdown');
        },

        focus: function() {
            return this.widgetFunction('focus');
        },

        search: function() {
            Array.prototype.unshift.call(arguments, 'search');
            return this.widgetFunction.apply(this, arguments);
        }
    });

    return Select2InputWidget;
});
