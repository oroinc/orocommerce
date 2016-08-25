define(function(require) {
    'use strict';

    var QuoteDemandComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var template = require('tpl!../../../templates/quote-to-order-item-error.html');

    if (typeof template === 'string') {
        template = _.template(template);
    }

    QuoteDemandComponent = BaseComponent.extend({
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
            this.blockQuantityUpdate = false;

            this.subtotalUrl = routing.generate(this.options.subtotalsRoute, {
                id: this.options.quoteDemandId
            });

            this.$form = this.$el;
            if (this.options.formSelector) {
                this.$form = this.$el.find(this.options.formSelector);
            }
            this.initFormValidation();

            $(this.options.lineItemsSelector).on('quote-items-changed', $.proxy(this.loadSubtotals, this));
        },

        initFormValidation: function() {
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

            $(this.options.lineItemsSelector).off('quote-items-changed', $.proxy(this.loadSubtotals, this));

            QuoteDemandComponent.__super__.dispose.call(this);
        },

        loadSubtotals: function() {
            if (this.subtotalUrl) {
                if (this.inProgress) {
                    return;
                }

                this.inProgress = true;
                this.$form.ajaxSubmit({
                    url: this.subtotalUrl,
                    data: {
                        '_widgetContainer': 'ajax'
                    },
                    success: _.bind(this.onSubtotalSuccess, this)
                });
            }
        },

        onSubtotalSuccess: function(response) {
            this.inProgress = false;
            var $response = $('<div/>').html(response);
            var $content = $(this.options.subtotalSelector);
            $content.html($response.find(this.options.subtotalSelector).html());
        }
    });

    return QuoteDemandComponent;
});
