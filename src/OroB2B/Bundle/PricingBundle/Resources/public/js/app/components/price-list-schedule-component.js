define(function(require) {
    'use strict';

    var PriceListScheduleComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');

    PriceListScheduleComponent = BaseComponent.extend({
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.$el.on('change content:remove', _.bind(this._handleErrors, this));
        },

        _handleErrors: function () {
            this.$el.find(".pl-schedule__error-row").remove();
            this.$el.find(".has-row-error").removeClass("has-row-error")
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.off('change content:remove', _.bind(this._handleErrors, this));
        }
    });

    return PriceListScheduleComponent;
});
