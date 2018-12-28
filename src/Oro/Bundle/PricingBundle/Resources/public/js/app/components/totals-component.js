define(function(require) {
    'use strict';

    var TotalsComponent;
    var subtotalTemplate = require('text!oropricing/templates/order/subtotals.html');
    var template = require('tpl!oropricing/templates/order/totals.html');
    var noDataTemplate = require('tpl!oropricing/templates/order/totals-no-data.html');
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var localeSettings = require('orolocale/js/locale-settings');

    /**
     * @export oropricing/js/app/components/totals-component
     * @extends oroui.app.components.base.Component
     * @class oropricing.app.components.TotalsComponent
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
                subtotalTemplate: null,
                template: null,
                noDataTemplate: null,
                totals: '[data-totals-container]'
            },
            events: ['update:totals'],
            skipMaskView: false,
            application: ''
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
        $totals: null,

        /**
         * @property {Object}
         */
        template: template,

        /**
         * @property {Object}
         */
        subtotalTemplate: subtotalTemplate,

        /**
         * @property {Object}
         */
        noDataTemplate: noDataTemplate,

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
         * @property {Array}
         */
        items: [],

        /**
         * @inheritDoc
         */
        constructor: function TotalsComponent() {
            TotalsComponent.__super__.constructor.apply(this, arguments);
        },

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
            this.$totals = this.$el.find(this.options.selectors.totals);

            this.resolveTemplates();

            this.loadingMaskView = new LoadingMaskView({container: this.$el});
            this.eventName = 'total-target:changing';

            this.initializeListeners();

            var totals = this.setDefaultTemplatesForData(this.options.data);

            this.render(totals);
        },

        resolveTemplates: function() {
            if (typeof this.options.selectors.template === 'string') {
                this.template = _.template($(this.options.selectors.template).text());
            }

            if (typeof this.options.selectors.subtotalTemplate === 'string') {
                this.subtotalTemplate = $(this.options.selectors.subtotalTemplate).text();
            }

            if (typeof this.options.selectors.noDataTemplate === 'string') {
                this.noDataTemplate = _.template($(this.options.selectors.noDataTemplate).text());
            }
        },

        setDefaultTemplatesForData: function(totals) {
            if (totals.subtotals) {
                var that = this;
                _.map(totals.subtotals, function(subtotal) {
                    if (!subtotal.template) {
                        subtotal.template = that.subtotalTemplate;
                    }

                    return subtotal;
                });
            }

            return totals;
        },

        initializeListeners: function() {
            _.each(this.options.events, function(event) {
                mediator.on(event, this.updateTotals, this);
            }, this);
        },

        showLoadingMask: function() {
            if (!this.options.skipMaskView) {
                this.loadingMaskView.show();
            }
        },

        hideLoadingMask: function() {
            if (this.loadingMaskView.isShown()) {
                this.loadingMaskView.hide();
            }
        },

        /**
         * Get and render totals
         */
        updateTotals: function(e) {
            this.showLoadingMask();

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
                    this.getTotals(_.bind(function(totals) {
                        this.hideLoadingMask();
                        this.triggerTotalsUpdateEvent(totals);
                        totals = this.setDefaultTemplatesForData(totals);
                        this.render(totals);
                    }, this));
                }
            }, this), 100);
        },

        /**
         * @param {Object} totals
         */
        triggerTotalsUpdateEvent: function(totals) {
            if (!_.isUndefined(totals) && !_.isEmpty(totals)) {
                mediator.trigger('totals:update', totals);
            }
        },

        /**
         * Get order totals
         *
         * @param {Function} callback
         */
        getTotals: function(callback) {
            var self = this;
            var typeRequest = 'GET';
            var data = null;

            var params = {
                entityClassName: this.options.entityClassName,
                entityId: this.options.entityId ? this.options.entityId : 0
            };

            var formData = this.$form.find(':input[data-ftid]').serialize();
            this.formData = formData;

            if (formData) {
                typeRequest = 'POST';
                data = formData;
            }

            $.ajax({
                url: routing.generate(this.options.route, params),
                type: typeRequest,
                data: data,
                success: function(response) {
                    if (formData === self.formData && !self.disposed) {
                        // data doesn't change after ajax call
                        var totals = response || {};
                        callback(totals);
                    }
                }
            });
        },

        /**
         * Render totals
         *
         * @param {Object} totals
         */
        render: function(totals) {
            this.items = [];

            _.each(totals.subtotals, _.bind(this.pushItem, this));

            this.pushItem(totals.total);

            var items = _.filter(this.items);
            if (_.isEmpty(items)) {
                items = this.noDataTemplate();
            }

            this.$totals.html(items.join(''));

            this.items = [];
        },

        /**
         * @param {Object} item
         */
        pushItem: function(item) {
            var localItem = _.defaults(
                item,
                {
                    amount: 0,
                    currency: localeSettings.getCurrency(),
                    visible: false,
                    template: null,
                    signedAmount: 0,
                    data: {}
                }
            );

            if (localItem.visible === false) {
                return;
            }

            item.formattedAmount = NumberFormatter.formatCurrency(item.signedAmount, item.currency);

            if (item.data && item.data.baseAmount && item.data.baseCurrency) {
                item.formattedBaseAmount = NumberFormatter.formatCurrency(
                    item.data.baseAmount,
                    item.data.baseCurrency
                );
            }

            var renderedItem = null;

            if (localItem.template) {
                renderedItem = _.template(item.template)({item: item});
            } else {
                renderedItem = this.template({item: item});
            }

            this.items.push(renderedItem);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.items;

            mediator.off(null, null, this);

            TotalsComponent.__super__.dispose.call(this);
        }
    });

    return TotalsComponent;
});
