define(function(require) {
    'use strict';

    var RadioInputWidget;
    var $ = require('jquery');
    var _ = require('underscore');
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');

    RadioInputWidget = AbstractInputWidget.extend({
        widgetFunction: function() {
            this.findContainer();
            this.$el.on('change', _.bind(this._handleChange, this));
            this.getContainer().on('keydown keypress', _.bind(this._handleEnterPress, this));
        },

        _handleEnterPress: function (event) {
            if (event.which === 32) {
                event.preventDefault();
                this.$el.trigger('click');
            }
        },

        _handleChange: function(event) {
            var inputName = this.$el.attr('name');

            if (this.$el.is(':checked')) {
                $('input[type="radio"][name="' + inputName + '"]')
                    .removeProp('checked')
                    .closest('label').removeClass('checked');

                this.$el.prop('checked', 'checked');
                this.getContainer().addClass('checked');
            }
        },

        findContainer: function() {
            this.$container = this.$el.closest('label');
        }
    });

    return RadioInputWidget;
});
