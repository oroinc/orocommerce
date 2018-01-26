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
        reloadEvents: [],
        buttonDisabled: false,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            SinglePageTransitionButtonComponent.__super__.initialize.call(this, options);
            if (this.options.saveStateUrl || false) {
                this.saveOnChange = _.debounce(this.saveOnChange, this.options.changeTimeout);

                this.$form.on('change', _.bind(this.onFormChange, this));
            }
            if (this.$form) {
                this.$el.on('click', _.bind(this.submit, this));
            }
            this.createAjaxData();
        },

        /**
         * @param {jQuery.Event} e
         */
        submit: function(e) {
            e.preventDefault();
            this.$form.trigger('submit');
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
            if (!Boolean(e.originalEvent)) {
                return;
            }

            var $target = $(e.target);

            this.isReloadRequired($target);
            this.saveOnChange($target);
        },

        /**
         * @param {jQuery} $target
         */
        isReloadRequired: function($target) {
            var self = this;

            _.each(this.options.targetEvents, function(eventNames, selector) {
                if (!$target.closest(selector).length) {
                    return;
                }

                _.each(eventNames, function(eventName) {
                    if (_.indexOf(self.reloadEvents, eventName) === -1) {
                        self.reloadEvents.push(eventName);
                    }
                });
            });

            if (!_.isEmpty(this.reloadEvents) && !this.buttonDisabled) {
                mediator.trigger('checkout:transition-button:disable');
                this.buttonDisabled = true;
            }
        },

        /**
         * @param {jQuery} $target
         */
        saveOnChange: function($target) {
            if (this.inProgress) {
                return;
            }

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
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.$form) {
                this.$el.off('click', _.bind(this.submit, this));
            }

            SinglePageTransitionButtonComponent.__super__.dispose.call(this);
        },

        /**
         * @param {jQuery.Element} $target
         * @param {Object} response
         */
        afterSaveState: function($target, response) {
            var self = this;
            var responseData = response.responseData || {};
            if (!_.isEmpty(this.reloadEvents) && (responseData.stateSaved || false)) {
                var eventCount = this.reloadEvents.length;

                _.each(this.reloadEvents, function(eventName) {
                    mediator.trigger(
                        eventName,
                        {
                            layoutSubtreeCallback: function() {
                                eventCount--;

                                if (eventCount < 1) {
                                    mediator.trigger('checkout:transition-button:enable');
                                    self.reloadEvents = [];
                                    self.buttonDisabled = false;
                                }
                            }
                        }
                    );
                });
            }
        }
    });

    return SinglePageTransitionButtonComponent;
});
