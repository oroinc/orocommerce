define(function(require) {
    'use strict';

    var ChosenInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    require('chosen');

    ChosenInputWidget = AbstractInputWidget.extend({
        widgetFunctionName: 'chosen',

        initializeOptions: {
            disable_search_threshold: 10,
            width: '100%'
        },

        destroyOptions: 'destroy',

        refreshOptions: 'update',

        containerClassSuffix: 'select',

        refreshOnChange: true,

        isInitialized: function() {
            return this.$el.data('chosen') ? true : false;
        },

        refresh: function() {
            this.$el.trigger('chosen:updated');
        },

        findContainer: function() {
            this.$container = this.$el.data('chosen').container;
        }
    });

    return ChosenInputWidget;
});
