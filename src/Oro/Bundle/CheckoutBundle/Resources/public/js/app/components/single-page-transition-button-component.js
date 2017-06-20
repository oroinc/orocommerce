define(function(require) {
    'use strict';

    var TransitionButtonComponent = require('orocheckout/js/app/components/transition-button-component');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    var SinglePageTransitionButtonComponent = TransitionButtonComponent.extend({
        defaults: _.extend({}, TransitionButtonComponent.prototype.defaults, {
            saveStateUrl: null,
            initialEvents: [],
            targetEvents: {},
            changeTimeout: 1500
        }),

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            SinglePageTransitionButtonComponent.__super__.initialize.call(this, options);

            if (this.options.saveStateUrl || false) {
                $.each(this.options.initialEvents, function(index, eventName) {
                    mediator.trigger(eventName);
                });

                this.$form.on('change', _.debounce($.proxy(this.onFormChange, this), this.options.changeTimeout));
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        onFormChange: function(e) {
            var ajaxData = this.prepareAjaxData({method: 'POST'}, this.options.saveStateUrl);

            $.ajax(ajaxData)
                .done(_.bind(this.afterSaveState, this, e.target));
        },

        /**
         * @param {HTMLElement} target
         * @param {Object} response
         */
        afterSaveState: function(target, response) {
            var responseData = response.responseData || {};
            if (responseData.stateSaved || false) {
                var $target = $(target);

                $.each(this.options.targetEvents, function(selector, eventName) {
                    if ($target.closest(selector).length) {
                        mediator.trigger(eventName);
                    }
                });
            }
        }
    });

    return SinglePageTransitionButtonComponent;
});
