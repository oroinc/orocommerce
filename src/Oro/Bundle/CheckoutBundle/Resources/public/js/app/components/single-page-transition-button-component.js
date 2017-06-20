define(function(require) {
    'use strict';

    var TransitionButtonComponent = require('orocheckout/js/app/components/payment-transition-button-component');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    var SinglePageTransitionButtonComponent = TransitionButtonComponent.extend({
        defaults: _.extend({}, TransitionButtonComponent.prototype.defaults, {
            saveStateUrl: null,
            initialEvents: [],
            targetEvents: {},
            ignoreTargets: {},
            changeTimeout: 2500
        }),

        lastSavedData: '',

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            SinglePageTransitionButtonComponent.__super__.initialize.call(this, options);

            // disable validation for credit cards, it validate manually in credit cardcomponents
            _.each($('[data-credit-card-form] [data-validation]'), function(item) {
                $(item).data('validation', {});
            });

            if (this.options.saveStateUrl || false) {
                $.each(this.options.initialEvents, function(index, eventName) {
                    mediator.trigger(eventName);
                });

                this.$form.on('change', _.debounce($.proxy(this.onFormChange, this), this.options.changeTimeout));
            }
        },

        /**
         * @inheritDoc
         */
        serializeForm: function() {
            var formName = this.$form.attr('name');
            return this.$form.find('[name^='+ formName +']').serialize();
        },

        /**
         * @param {jQuery.Event} e
         */
        onFormChange: function(e) {
            if (this.inProgress) {
                return;
            }

            var $target = $(e.target);

            for (var i in this.options.ignoreTargets) {
                var selector  = this.options.ignoreTargets[i];
                if ($target.closest(selector).length) {
                    return;
                }
            }

            var ajaxData = this.prepareAjaxData({method: 'POST'}, this.options.saveStateUrl);
            if (this.lastSavedData === ajaxData.data) {
                return;
            }

            this.lastSavedData = ajaxData.data;
            $.ajax(ajaxData)
                .done(_.bind(this.afterSaveState, this, $target))
        },

        /**
         * @param {jQuery.Element} $target
         * @param {Object} response
         */
        afterSaveState: function($target, response) {
            var responseData = response.responseData || {};
            if (responseData.stateSaved || false) {
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
