define(function(require) {
    'use strict';

    var QuoteDemandComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');

    QuoteDemandComponent = BaseComponent.extend({
        options: {
            subtotalsRoute: 'oro_sale_quote_frontend_subtotals',
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

        /**
         * @inheritDoc
         */
        constructor: function QuoteDemandComponent() {
            QuoteDemandComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = options._sourceElement;

            this.subtotalUrl = routing.generate(this.options.subtotalsRoute, {
                id: this.options.quoteDemandId
            });

            this.$form = this.$el.find(this.options.formSelector || 'form');

            this.loadSubtotals = this.loadSubtotals.bind(this);

            $(this.options.lineItemsSelector).on('quote-items-changed', this.loadSubtotals);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            $(this.options.lineItemsSelector).off('quote-items-changed', this.loadSubtotals);

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
                        _widgetContainer: 'ajax'
                    },
                    success: this.onSubtotalSuccess.bind(this)
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
