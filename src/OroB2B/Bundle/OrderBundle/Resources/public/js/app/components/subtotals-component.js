define(function(require) {
    'use strict';

    var SubtotalsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
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

            this.updateSubtotals();

            mediator.on('order-subtotals:update', this.updateSubtotals, this);
        },

        /**
         * Get and render subtotals
         */
        updateSubtotals: function(e) {
            this.loadingMaskView.show();

            if (this.getSubtotals.timeoutId) {
                clearTimeout(this.getSubtotals.timeoutId);
            }

            this.getSubtotals.timeoutId = setTimeout(_.bind(function() {
                this.getSubtotals.timeoutId = null;

                var promises = [];
                mediator.trigger('order:changing', promises);

                if (promises.length) {
                    $.when.apply($, promises).done(_.bind(this.updateSubtotals, this, e));
                } else {
                    this.getSubtotals(_.bind(function(subtotals) {
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
        getSubtotals: function(callback) {
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
                    var subtotals = response.subtotals || {};
                    callback(subtotals);
                }
            });
        },

        /**
         * Render subtotals
         *
         * @param {Object} subtotals
         */
        render: function(subtotals) {
            _.each(subtotals, function(subtotal) {
                subtotal.formattedAmount = NumberFormatter.formatCurrency(subtotal.amount, subtotal.currency);
            });

            this.$subtotals.html(this.template({
                subtotals: subtotals
            }));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order-subtotals:update', this.updateSubtotals, this);

            SubtotalsComponent.__super__.dispose.call(this);
        }
    });

    return SubtotalsComponent;
});
