define(function(require) {
    'use strict';

    var TransitionButtonComponent = require('orocheckout/js/app/components/payment-transition-button-component');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    var SinglePageTransitionButtonComponent = TransitionButtonComponent.extend({
        defaults: _.extend({}, TransitionButtonComponent.prototype.defaults, {
            saveStateUrl: null,
            targetEvents: {},
            ignoreTargets: {},
            changeTimeout: 1500
        }),

        lastSavedData: '',

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            SinglePageTransitionButtonComponent.__super__.initialize.call(this, options);

            if (this.options.saveStateUrl || false) {
                this.$form.on('change', _.debounce($.proxy(this.onFormChange, this), this.options.changeTimeout));
            }

            this.createAjaxData();
        },

        /**
         * @inheritDoc
         */
        serializeForm: function() {
            var formName = this.$form.attr('name');
            return this.$form.find('[name^=' + formName + ']').serialize();
        },

        /**
         * @returns {Object|null}
         */
        createAjaxData: function() {
            var ajaxData = this.prepareAjaxData({method: 'POST'}, this.options.saveStateUrl);
            if (this.lastSavedData === ajaxData.data) {
                return null;
            }

            this.lastSavedData = ajaxData.data;

            return ajaxData;
        },

        /**
         * @param {jQuery.Event} e
         */
        onFormChange: function(e) {
            if (this.inProgress) {
                return;
            }

            var $target = $(e.target);

            for (var i = 0; i < this.options.ignoreTargets.length; i++) {
                var selector = this.options.ignoreTargets[i];
                if ($target.closest(selector).length) {
                    return;
                }
            }

            var ajaxData = this.createAjaxData();
            if (null === ajaxData) {
                return;
            }

            $.ajax(ajaxData)
                .done(_.bind(this.afterSaveState, this, $target));
        },

        /**
         * @param {jQuery.Element} $target
         * @param {Object} response
         */
        afterSaveState: function($target, response) {
            var responseData = response.responseData || {};
            if (!_.isEmpty(this.options.targetEvents) && (responseData.stateSaved || false)) {
                var eventCount = 0;
                var disabled = false;

                $.each(this.options.targetEvents, function(selector, eventNames) {
                    eventCount += eventNames.length;

                    if ($target.closest(selector).length) {
                        if (!disabled) {
                            mediator.trigger('checkout:transition-button:disable');
                            disabled = true;
                        }

                        _.each(eventNames, function(eventName) {
                            mediator.trigger(
                                eventName,
                                {
                                    'layoutSubtreeCallback': function() {
                                        eventCount--;

                                        if (eventCount < 1) {
                                            mediator.trigger('checkout:transition-button:enable');
                                        }
                                    }
                                }
                            );
                        });
                    }
                });
            }
        }
    });

    return SinglePageTransitionButtonComponent;
});
