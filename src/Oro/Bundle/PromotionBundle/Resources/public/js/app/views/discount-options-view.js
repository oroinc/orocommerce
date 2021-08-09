define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');

    const DiscountOptionsView = BaseView.extend({
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
         * @inheritdoc
         */
        constructor: function DiscountOptionsView(options) {
            DiscountOptionsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            const $el = this.options.el;
            this.$formContainer = $el.find(this.options.selectors.formContainerSelector);
            $el.on('change', this.options.selectors.formTypesChoiceSelector, this.changeDiscountForm.bind(this));
        },

        /**
         * @param {jquery.Event} event
         */
        changeDiscountForm: function(event) {
            const currentFormName = $(event.target).val();

            this.$formContainer
                .trigger('content:remove')
                .html(this.options.discountFormPrototypes[currentFormName])
                .trigger('content:changed');
            mediator.execute('layout:init', this.$formContainer);
        },

        /**
         * @inheritdoc
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
