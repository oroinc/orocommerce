define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const UnitsUtil = require('oroproduct/js/app/units-util');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const routing = require('routing');

    /**
     * @export ororfp/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class ororfp.app.views.LineItemView
     */
    const LineItemView = BaseView.extend(_.extend({}, ElementsHelper, {
        /**
         * @property {Object}
         */
        options: {
            ftid: '',
            selectors: {
                quantitySelector: '[data-name="field__quantity"]',
                unitSelector: '[data-name="field__product-unit"]'
            },
            syncClass: 'synchronized',
            unitsRoute: 'oro_product_frontend_ajaxproductunit_productunits',
            compactUnits: false,
            kitItemLineItemsRoute: 'oro_rfp_request_product_kit_item_line_item_entry_point',
            itemWidget: '[data-role="lineitem"]',
            skipLoadingMask: false
        },

        /**
         * @property {Object}
         */
        $el: null,

        /**
         * @property {array}
         */
        units: {},

        /**
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        elements: {
            productId: '[data-name="field__product"]',
            kitItemLineItems: '.rfp-lineitem-product .rfp-lineitem-kit-item-line-items'
        },

        modelElements: {
            productId: 'productId'
        },

        modelAttr: {
            productId: 0,
            productType: 'simple',
            sku: '',
            product_units: {}
        },

        modelEvents: {
            productId: ['change', 'onProductChanged']
        },

        /**
         * @inheritdoc
         */
        constructor: function LineItemView(options) {
            LineItemView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            if (!this.options.ftid) {
                this.options.ftid = this.$el.data('content').toString()
                    .replace(/[^a-zA-Z0-9]+/g, '_').replace(/_+$/, '');
            }

            this.delegate('click', '.removeLineItem', this.removeRow);

            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.$el.on('content:changed', this.onContentChanged.bind(this));

            this.initModel(options);
            this.initializeElements(options);
            this.model.set('product_units', this.options.units[this.model.get('productId')] || {});

            this.$el.on('options:set:lineItemModel', (e, options) => {
                options.lineItemModel = this.model;
            });

            this.initializeSubviews({
                lineItemModel: this.model
            });
        },

        removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();
        },

        /**
         * Handle change
         */
        onProductChanged: function(data) {
            if (data !== void 0 && data.event) {
                this.model.set('sku', data.event.added.sku);
                this.model.set('productType', data.event.added.type || 'simple');
            }

            const productId = this.model.get('productId');

            if (productId) {
                const self = this;
                const routeParams = {id: productId};

                $.ajax({
                    url: routing.generate(this.options.kitItemLineItemsRoute, routeParams),
                    type: 'POST',
                    data: $.param(this.getData()),
                    beforeSend: function() {
                        if (!self.options.skipLoadingMask) {
                            self.loadingMask.show();
                        }
                    },
                    success: function(response) {
                        self.updateKitItemLineItems(response);
                        self.loadingMask.hide();
                    },
                    complete: function() {
                        self.loadingMask.hide();
                    },
                    errorHandlerMessage: __(this.options.errorMessage)
                });
            }
        },

        updateKitItemLineItems: function(response) {
            this.getElement('kitItemLineItems')
                .html(response || '')
                .trigger('content:changed');
        },

        /**
         * Handle change
         *
         * @param {jQuery.Event} e
         */
        onContentChanged: function(e) {
            this.updateContent(false);
        },

        /**
         * @param {Boolean} force
         */
        updateContent: function(force) {
            const productId = this.model.get('productId');
            const productUnits = this.units[productId];

            if (!productId || productUnits) {
                this.updateProductUnits(productUnits, force || false);
            } else {
                const self = this;
                const routeParams = {id: productId};

                if (this.options.compactUnits) {
                    routeParams.short = true;
                }

                $.ajax({
                    url: routing.generate(this.options.unitsRoute, routeParams),
                    type: 'GET',
                    beforeSend: function() {
                        if (!self.options.skipLoadingMask) {
                            self.loadingMask.show();
                        }
                    },
                    success: function(response) {
                        self.units[productId] = response.units;
                        self.updateProductUnits(response.units, true);
                    },
                    complete: function() {
                        self.loadingMask.hide();
                    },
                    errorHandlerMessage: __(this.options.errorMessage)
                });
            }
        },

        /**
         * Update available ProductUnit select
         *
         * @param {Object} units
         * @param {Boolean} force
         */
        updateProductUnits: function(units, force) {
            const self = this;

            this.model.set('product_units', units);

            const widgets = self.$el.find(self.options.itemWidget);
            $.each(widgets, function(index, widget) {
                const $select = $(widget).find(self.options.selectors.unitSelector);

                if (!force && $select.hasClass(self.options.syncClass)) {
                    return;
                }

                UnitsUtil.updateSelect(self.model, $select);
                $select.addClass(self.options.syncClass);
            });

            if (force) {
                this.$el.find('select').inputWidget('refresh');
            }
        },

        /**
         * @return {Object}
         */
        getData: function() {
            return this.$elements.productId.serializeArray();
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            LineItemView.__super__.dispose.call(this);
        }
    }));

    return LineItemView;
});
