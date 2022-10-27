define(function(require) {
    'use strict';

    const subtotalTemplate = require('text-loader!oropricing/templates/order/subtotals.html');
    const template = require('tpl-loader!oropricing/templates/order/totals.html');
    const noDataTemplate = require('tpl-loader!oropricing/templates/order/totals-no-data.html');
    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const NumberFormatter = require('orolocale/js/formatter/number');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const localeSettings = require('orolocale/js/locale-settings');

    /**
     * @export oropricing/js/app/components/totals-component
     * @extends oroui.app.components.base.Component
     * @class oropricing.app.components.TotalsComponent
     */
    const TotalsComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function TotalsComponent(options) {
            TotalsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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

            const totals = this.setDefaultTemplatesForData(this.options.data);

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
                _.map(totals.subtotals, subtotal => {
                    if (!subtotal.template) {
                        subtotal.template = this.subtotalTemplate;
                    }

                    return subtotal;
                });
            }

            return totals;
        },

        initializeListeners: function() {
            _.each(this.options.events, event => {
                this.listenTo(mediator, event, this.updateTotals);
            });
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

            this.getTotals.timeoutId = setTimeout(() => {
                this.getTotals.timeoutId = null;

                const promises = [];
                mediator.trigger(this.eventName, promises);

                if (promises.length) {
                    $.when(...promises).done(this.updateTotals.bind(this, e));
                } else {
                    this.getTotals(totals => {
                        this.hideLoadingMask();
                        this.triggerTotalsUpdateEvent(totals);
                        totals = this.setDefaultTemplatesForData(totals);
                        this.render(totals);
                    });
                }
            }, 100);
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
            const self = this;
            let typeRequest = 'GET';
            let data = null;

            const params = {
                entityClassName: this.options.entityClassName,
                entityId: this.options.entityId ? this.options.entityId : 0
            };

            const formData = this.$form.find(':input[data-ftid]').serialize();
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
                        const totals = response || {};
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

            _.each(totals.subtotals, this.pushItem.bind(this));

            this.pushItem(totals.total);

            let items = _.filter(this.items);
            if (_.isEmpty(items)) {
                items = [this.noDataTemplate()];
            }

            this.$totals.html(items.join(''));

            this.items = [];
        },

        /**
         * @param {Object} item
         */
        pushItem: function(item) {
            const localItem = _.defaults(
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

            let renderedItem = null;

            if (localItem.template) {
                renderedItem = _.template(item.template)({item: item});
            } else {
                renderedItem = this.template({item: item});
            }

            this.items.push(renderedItem);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.items;

            TotalsComponent.__super__.dispose.call(this);
        }
    });

    return TotalsComponent;
});
