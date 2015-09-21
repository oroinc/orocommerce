define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var BaseView = require('oroui/js/app/views/base/view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');

    /**
     * @export orob2brfp/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2brfp.app.views.LineItemView
     */
    LineItemView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            ftid: '',
            selectors: {
                productSelector: '.rfp-lineitem-product input.select2',
                quantitySelector: '.rfp-lineitem-requested-quantity input',
                unitSelector: '.rfp-lineitem-requested-unit select',
                priceSelector: '.rfp-lineitem-requested-price input',
                currencySelector: '.rfp-lineitem-requested-currency select'
            },
            unitLoaderRouteName: 'orob2b_pricing_frontend_units_by_pricelist',
            unitsRoute: 'orob2b_product_frontend_ajaxproductunit_productunits',
            itemsContainer: '.rfp-lineitem-requested-items',
            itemWidget: '.rfp-lineitem-requested-item',
            addItemButton: '.rfp-lineitem-requested-item-add'
        },

        /**
         * @property {Object}
         */
        $el: null,

        /**
         * @property {Object}
         */
        $productSelect: null,

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

            this.$productSelect = this.$el.find(this.options.selectors.productSelector);
            this.$itemsContainer = this.$el.find(this.options.itemsContainer);
            this.$addItemButton = this.$el.find(this.options.addItemButton);
            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.$el
                .on('change', this.options.selectors.productSelector, _.bind(this.onProductChanged, this))
                .on('content:changed', _.bind(this.onContentChanged, this))
            ;

            this.checkAddButton();
        },

        checkAddButton: function() {
            this.$addItemButton.toggle(Boolean(this.$productSelect.val()));
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
         *
         * @param {jQuery.Event} e
         */
        onProductChanged: function(e) {
            this.checkAddButton();
            if (this.$productSelect.val() && !this.$itemsContainer.children().length) {
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
            var productId = this.$productSelect.val();
            var productUnits = this.units[productId];

            if (!productId || productUnits) {
                this.updateProductUnits(productUnits, force || false);
            } else {
                var self = this;
                $.ajax({
                    url: routing.generate(this.options.unitsRoute, {'id': productId}),
                    type: 'GET',
                    beforeSend: function() {
                        self.loadingMask.show();
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
         * @param {Object} data
         * @param {Boolean} force
         */
        updateProductUnits: function(data, force) {
            var self = this;

            var units = data || {};

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
                    $(widget).find('select').uniform('update');
                }
            });

            if (force) {
                this.$el.find('select').uniform('update');
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
        },
    });

    return LineItemView;
});
