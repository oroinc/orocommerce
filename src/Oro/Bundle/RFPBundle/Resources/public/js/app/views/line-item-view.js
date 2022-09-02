define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const BaseModel = require('oroui/js/app/models/base/model');
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
            itemsContainer: '[data-role="lineitems"]',
            itemWidget: '[data-role="lineitem"]',
            addItemButton: '[data-role="lineitem-add"]',
            skipLoadingMask: false
        },

        /**
         * @property {Object}
         */
        $el: null,

        /**
         * @property {Object}
         */
        $itemsContainer: null,

        /**
         * @property {Object}
         */
        $addItemButton: null,

        /**
         * @property {array}
         */
        units: {},

        /**
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        elements: {
            productId: '[data-name="field__product"]'
        },

        modelElements: {
            productId: 'productId'
        },

        modelAttr: {
            productId: 0,
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

            this.$itemsContainer = this.$el.find(this.options.itemsContainer);
            this.$addItemButton = this.$el.find(this.options.addItemButton);
            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.$el.on('content:changed', this.onContentChanged.bind(this));

            this.initModel(options);
            this.initializeElements(options);
            this.model.set('product_units', this.options.units[this.model.get('productId')] || []);

            this.$el.on('options:set:lineItemModel', (e, options) => {
                options.lineItemModel = this.model;
            });

            this.initializeSubviews({
                lineItemModel: this.model
            });
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            this.model = new BaseModel();

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        handleLayoutInit: function() {
            this.checkAddButton();
            this._resolveDeferredRender();
        },

        checkAddButton: function() {
            this.$addItemButton.toggle(Boolean(this.model.get('productId')));
        },

        initSelects: function() {
            this.$el.find(this.options.unitsSelect).addClass(this.options.syncClass);
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
            }

            this.checkAddButton();
            if (this.model.get('productId') && !this.$itemsContainer.children().length) {
                this.$addItemButton.click();
            }
            if (this.$itemsContainer.children().length) {
                this.updateContent(true);
            }
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
