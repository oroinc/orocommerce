define(function(require) {
    'use strict';

    var PriceListScheduleComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');

    PriceListScheduleComponent = BaseComponent.extend({
        options: {
            subtotalsRoute: 'orob2b_sale_quote_frontend_subtotals',
            quoteDemandId: null,
            subtotalSelector: null,
            formSelector: null,
            lineItemsSelector: null
        },

        /**
         * @property {jQuery.Element}
         */
        $form: null,

        /**
         * @property {String}
         */
        subtotalUrl: null,

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = options._sourceElement;
            //this.$form = this.$el;
            //this.initFormValidation();
        },

        initFormValidation: function() {
            this.$el.find('input').validate(
                {
                    errorClass: 'error',
                    showErrors: function(errorMap, errorList) {
                        var $element = $(this.currentElements[0]);
                        var $container = $element.closest('tbody').find('pl-schedule__row_error td');

                        $container.html('');
                        $element.removeClass(this.settings.errorClass);
                        if (errorList.length) {
                            $element.addClass(this.settings.errorClass);
                            _.each(errorMap, function(message) {
                                $(template({'message': message})).appendTo($container);
                            });
                        }
                    }
                }
            );
        },

        initFormRowValidation: function() {
            this.$form.validate(
                {
                    errorClass: 'error',
                    showErrors: function(errorMap, errorList) {
                        var $element = $(this.currentElements[0]);
                        var $container = $element.closest('td');

                        $container.find('[data-role="error-container"]').remove();
                        $element.removeClass(this.settings.errorClass);
                        if (errorList.length) {
                            $element.addClass(this.settings.errorClass);
                            _.each(errorMap, function(message) {
                                $(template({'message': message})).appendTo($container);
                            });
                        }
                    }
                }
            );
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            //$(this.options.lineItemsSelector).off('quote-items-changed', _.bind(this.loadSubtotals, this));
        }
    });

    return PriceListScheduleComponent;
});
