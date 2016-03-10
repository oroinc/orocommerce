/** @lends TransitionButtonComponent */
define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    var TransitionButtonComponent;
    TransitionButtonComponent = BaseComponent.extend(/** @exports TransitionButtonComponent.prototype */{
        defaults: {
            transitionUrl: null,
            message: null,
            conditionMessages: [],
            enabled: true
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.defaults, options);
            this.inProgress = false;

            this.$el = options._sourceElement;
            this.$el.on('click', _.bind(this.transit, this));
        },

        transit: function() {
            if (!this.options.enabled || this.inProgress) {
                return;
            }

            // TODO: Allow checkout to save transition form and perform transition
            // TODO: Use checkout transition form action as endpoint to submit form and perform transition
            // TODO: If transition has no form - call API transit
            // TODO: Add ability to replace this default handler with custom using frontend-options
            this.inProgress = true;
            mediator.execute('showLoading');
            $.getJSON(this.options.transitionUrl)
                .done(_.bind(this.onSuccess, this))
                .fail(_.bind(this.onFail, this))
                .always(
                    _.bind(function() {
                        mediator.execute('hideLoading');
                        this.inProgress = false;
                    }, this)
                );
        },

        onSuccess: function() {
            
        },

        onFail: function() {

        }
    });

    return TransitionButtonComponent;
});
