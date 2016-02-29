define(function(require) {
    'use strict';

    var TotalsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2bpricing/js/app/components/totals-component
     * @extends oroui.app.components.base.Component
     * @class orob2bpricing.app.components.TotalsComponent
     */
    TotalsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            url: '',
            selectors: {
                form: '',
                template: '.totals-template',
                subtotals: '.totals-container'
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
         * @property {String}
         */
        eventName: '',

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            if (this.options.url.length === 0) {
                return;
            }

            this.$el = options._sourceElement;
            this.$form = $(this.options.selectors.form);
            this.$subtotals = this.$el.find(this.options.selectors.subtotals);
            this.template = _.template(this.$el.find(this.options.selectors.template).text());
            this.loadingMaskView = new LoadingMaskView({container: this.$el});
            this.eventName = 'total-target:changing';

            this.updateTotals();

            mediator.on('line-items-totals:update', this.updateTotals, this);
            mediator.on('update:account', this.updateTotals, this);
            mediator.on('update:website', this.updateTotals, this);
            mediator.on('update:currency', this.updateTotals, this);
        },

        /**
         * Get and render subtotals
         */
        updateTotals: function(e) {
            this.loadingMaskView.show();

            if (this.getTotals.timeoutId) {
                clearTimeout(this.getTotals.timeoutId);
            }

            this.getTotals.timeoutId = setTimeout(_.bind(function() {
                this.getTotals.timeoutId = null;

                var promises = [];
                mediator.trigger(this.eventName, promises);

                if (promises.length) {
                    $.when.apply($, promises).done(_.bind(this.updateTotals, this, e));
                } else {
                    this.getTotals(_.bind(function(subtotals) {
                        this.loadingMaskView.hide();
                        if (!subtotals) {
                            return;
                        }
                        this.render(subtotals);
                    }, this));
                }
            }, this), 100);
        },

        /**
         * Get order subtotals
         *
         * @param {Function} callback
         */
        getTotals: function(callback) {
            var formData = this.$form.find(':input[data-ftid]').serialize();

            if (formData === this.formData) {
                callback();//nothing changed
                return;
            }

            this.formData = formData;

            var self = this;
            $.post(this.options.url, formData, function(response) {
                if (formData === self.formData) {
                    //data doesn't change after ajax call
                    var totals = response || {};
                    callback(totals);
                }
            });
        },

        /**
         * Render totals
         *
         * @param {Object} totals
         */
        render: function(totals) {
            _.each(totals.subtotals, function(subtotal) {
                subtotal.formattedAmount = NumberFormatter.formatCurrency(subtotal.amount, subtotal.currency);
            });

            totals.total.formattedAmount = NumberFormatter.formatCurrency(totals.total.amount, totals.total.currency);

            this.$subtotals.html(this.template({
                totals: totals
            }));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('line-items-totals:update', this.updateTotals, this);
            mediator.off('update:account', this.updateTotals, this);
            mediator.off('update:website', this.updateTotals, this);
            mediator.off('update:currency', this.updateTotals, this);
            TotalsComponent.__super__.dispose.call(this);
        }
    });

    return TotalsComponent;
});
