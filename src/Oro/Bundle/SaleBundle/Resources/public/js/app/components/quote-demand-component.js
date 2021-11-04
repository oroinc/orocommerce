define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const _ = require('underscore');
    const $ = require('jquery');
    const routing = require('routing');

    const QuoteDemandComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function QuoteDemandComponent(options) {
            QuoteDemandComponent.__super__.constructor.call(this, options);
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
            const $response = $('<div/>').html(response);
            const $content = $(this.options.subtotalSelector);
            $content.html($response.find(this.options.subtotalSelector).html());
        }
    });

    return QuoteDemandComponent;
});
