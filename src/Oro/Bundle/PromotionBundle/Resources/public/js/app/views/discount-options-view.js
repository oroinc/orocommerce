define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var DiscountOptionsView;

    DiscountOptionsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                formContainerSelector: '[data-role="discount-options-form"]',
                formTypesChoiceSelector: '[data-role="discount-form-choice"]'
            }
        },

        /**
         * @property {Element}
         */
        $formContainer: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            var $el = this.options.el;
            this.$formContainer = $el.find(this.options.selectors.formContainerSelector);
            $el.on('change', this.options.selectors.formTypesChoiceSelector, _.bind(this.changeDiscountForm, this));
        },

        /**
         * @param {jquery.Event} event
         */
        changeDiscountForm: function(event) {
            var currentFormName = $(event.target).val();

            this.$formContainer
                .trigger('content:remove')
                .html(this.options.discountFormPrototypes[currentFormName])
                .trigger('content:changed');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.$formContainer;
            DiscountOptionsView.__super__.dispose.call(this);
        }
    });

    return DiscountOptionsView;
});
