define(function(require) {
    'use strict';

    var SubtotalsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2border/js/app/components/subtotals-component
     * @extends oroui.app.components.base.Component
     * @class orob2border.app.components.SubtotalsComponent
     */
    SubtotalsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            url: '',
            selectors: {
                form: '',
                template: '.subtotals-template',
                subtotals: '.subtotals-container'
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $subtotals: null,

        /**
         * @property {Object}
         */
        template: null,

        /**
         * @property {String}
         */
        formData: '',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, this.options, options || {});

            this.$el = options._sourceElement;

            this.$form = $(this.options.selectors.form);

            this.$subtotals = this.$el.find(this.options.selectors.subtotals);

            this.template = _.template(this.$el.find(this.options.selectors.template).text());

            this.getSubtotals(_.bind(this.render, this));

            var self = this;
            mediator.on('order-subtotals:update', function() {
                self.getSubtotals(_.bind(self.render, self));
            });
        },

        /**
         * Get order subtotals
         *
         * @param {Function} callback
         */
        getSubtotals: function(callback) {
            var formData = this.$form.serialize();

            if (formData === this.formData) {
                return null;//nothing changed
            }

            this.formData = formData;

            var self = this;
            $.ajax({
                url: this.options.url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (formData === self.formData) {
                        //data doesn't change after ajax call
                        var subtotals = response.subtotals || {};
                        callback(subtotals);
                    }
                }
            });
        },

        /**
         * Render subtotals
         *
         * @param {Object} subtotals
         */
        render: function(subtotals) {
            if (!subtotals) {
                return null;
            }

            $.each(subtotals, function() {
                this.formatedAmount = NumberFormatter.formatCurrency(this.amount, this.currency);
            });

            this.$subtotals.html(this.template({
                subtotals: subtotals
            }));
        }
    });

    return SubtotalsComponent;
});
