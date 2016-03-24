define(function(require) {
    'use strict';

    var TotalsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var messenger =  require('oroui/js/messenger');
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
            route: '',
            entityClassName: '',
            entityId: 0,
            selectors: {
                form: '',
                template: '.totals-template',
                subtotals: '.totals-container'
            },
            events: [],
            skipMaskView: false
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
        $method: null,

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

            if (this.options.route.length === 0) {
                return;
            }

            this.$el = options._sourceElement;
            this.$form = $(this.options.selectors.form);
            this.$subtotals = this.$el.find(this.options.selectors.subtotals);
            this.template = _.template($(this.options.selectors.template).text());
            this.loadingMaskView = new LoadingMaskView({container: this.$el});
            this.eventName = 'total-target:changing';

            this.render(this.options.data);

            var self = this;
            _.each(this.options.events, function(event) {
                mediator.on(event, self.updateTotals, self);
            });
        },

        /**
         * Get and render subtotals
         */
        updateTotals: function(e) {
            if (!this.options.skipMaskView) {
                this.loadingMaskView.show();
            }

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

                        mediator.trigger('totals:update', subtotals);

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
            var self = this;

            var params = {
                entityClassName: this.options.entityClassName,
                entityId: this.options.entityId ? this.options.entityId : 0
            };

            var formData = this.$form.find(':input[data-ftid]').serialize();
            this.formData = formData;

            if (formData) {
                $.post(routing.generate(this.options.route, params), formData, function(response) {
                    if (formData === self.formData) {
                        //data doesn't change after ajax call
                        var totals = response || {};
                        callback(totals);
                    }
                });
            } else {
                $.ajax({
                    url: routing.generate(this.options.route, params),
                    type: 'GET',
                    success: function (response) {
                        if (formData === self.formData) {
                            //data doesn't change after ajax call
                            var totals = response || {};
                            callback(totals);
                        }
                    },
                    error: function(jqXHR) {
                        messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                    }
                });
            }
        },

        /**
         * Render totals
         *
         * @param {Object} totals
         */
        render: function(totals) {
            if (totals) {
                _.each(totals.subtotals, function (subtotal) {
                    subtotal.formattedAmount = NumberFormatter.formatCurrency(subtotal.amount, subtotal.currency);
                });

                totals.total.formattedAmount = NumberFormatter.formatCurrency(
                    totals.total.amount,
                    totals.total.currency
                );
            }

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

            var self = this;
            _.each(this.options.events, function(event) {
                mediator.off(event, self.updateTotals, self);
            });
            TotalsComponent.__super__.dispose.call(this);
        }
    });

    return TotalsComponent;
});
