define(function(require) {
    'use strict';

    var LineItemView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var BaseModel = require('oroui/js/app/models/base/model');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');

    /**
     * @export ororfp/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class ororfp.app.views.LineItemView
     */
    LineItemView = BaseView.extend(_.extend({}, ElementsHelper, {
        /**
         * @property {Object}
         */
        options: {
            ftid: '',
            selectors: {
                quantitySelector: '[data-name="field__quantity"]',
                unitSelector: '[data-name="field__product-unit"]',
                priceSelector: '[data-role="lineitem-price"]',
                currencySelector: '[data-role="lineitem-currency"]'
            },
            unitLoaderRouteName: 'oro_pricing_frontend_units_by_pricelist',
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
            productUnits: []
        },

        modelEvents: {
            'productId': ['change', 'onProductChanged']
        },

        /**
         * @inheritDoc
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

            this.$el.on('content:changed', _.bind(this.onContentChanged, this));

            this.initModel(options);
            this.initializeElements(options);
            this.model.set('productUnits', this.options.units[this.model.get('productId')] || []);

            this.$el.on('options:set:lineItemModel', _.bind(function(e, options) {
                options.lineItemModel = this.model;
            }, this));

            this._deferredRender();
            this.initLayout({
                lineItemModel: this.model
            }).done(_.bind(this.handleLayoutInit, this));
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
        onProductChanged: function() {
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
            var productId = this.model.get('productId');
            var productUnits = this.units[productId];

            if (!productId || productUnits) {
                this.updateProductUnits(productUnits, force || false);
            } else {
                var self = this;
                var routeParams = {'id': productId};

                if (this.options.compactUnits) {
                    routeParams['short'] = true;
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
                    error: function(xhr) {
                        self.loadingMask.hide();
                        messenger.showErrorMessage(__(self.options.errorMessage), xhr.responseJSON);
                    }
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
            var self = this;

            units = units || {};
            this.model.set('productUnits', units);

            var widgets = self.$el.find(self.options.itemWidget);
            $.each(widgets, function(index, widget) {
                var select = $(widget).find(self.options.selectors.unitSelector);

                if (!force && $(select).hasClass(self.options.syncClass)) {
                    return;
                }

                var currentValue = $(select).val();
                $(select).empty();
                $.each(units, function(key, value) {
                    $(select)
                        .append($('<option/>').val(key).text(value))
                    ;
                });
                var firstValue = $(select).find('option:first-child').val();
                if (!currentValue && firstValue) {
                    currentValue = firstValue;
                }
                $(select).val(currentValue);
                if (null === $(select).val() && firstValue) {
                    $(select).val(firstValue);
                }
                $(select).addClass(self.options.syncClass);

                if (!force) {
                    $(widget).find('select').inputWidget('refresh');
                }
            });

            if (force) {
                this.$el.find('select').inputWidget('refresh');
            }
        },

        /**
         * @inheritDoc
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
