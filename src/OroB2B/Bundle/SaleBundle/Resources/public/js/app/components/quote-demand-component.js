/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var QuoteDemandComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');

    QuoteDemandComponent = BaseComponent.extend({
        options: {
            subtotalsRoute: 'orob2b_sale_quote_frontend_subtotals',
            quoteDemandId: null,
            subtotalSelector: null,
            formSelector: null,
            lineItemsSelector: null,
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

            $(this.options.lineItemsSelector).on('quote-items-changed', _.bind(this.loadSubtotals, this));
        },

        loadSubtotals: function(value) {
            if (this.subtotalUrl) {
                this.$form.ajaxSubmit({
                    url: this.subtotalUrl,
                    data: {
                        '_widgetContainer': 'ajax',
                    },
                    success: _.bind(this.onSubtotalSuccess, this),
                    error: _.bind(this.onSubtotalFail, this)
                });
            }
        },

        onSubtotalSuccess: function(response) {
            var $response = $('<div/>').html(response);
            var $content = $(this.options.subtotalSelector);
            $content.html($response.find(this.options.subtotalSelector).html());
        },

        onSubtotalFail: function() {
            mediator.execute('showFlashMessage', 'error', 'Could not perform transition');
        },

    });

    return QuoteDemandComponent;
});
