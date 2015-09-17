/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var RequestProductItemsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');

    RequestProductItemsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            productSelect:  '.rfp-requestproduct-product-select input[type="hidden"]',
            unitsSelect:    '.rfp-requestproductitem-productunit-select',
            unitsRoute:     'orob2b_product_frontend_ajaxproductunit_productunits',
            addItemButton:  '.add-list-item',
            itemsContainer: '.rfp-requestproductitem-collection .oro-item-collection',
            itemWidget:     '.rfp-requestproductitem-widget',
            syncClass:      'synchronized',
            errorMessage:   'Sorry, unexpected error was occurred',
            units: {}
        },

        /**
         * @property {array}
         */
        units: {},

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
        $addItemButton: null,

        /**
         * @property {Object}
         */
        $itemsContainer: null,

        /**
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options    = _.defaults(options || {}, this.options);
            this.units      = _.defaults(this.units, options.units);

            this.$el = options._sourceElement;

            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.$productSelect     = this.$el.find(this.options.productSelect);
            this.$addItemButton     = this.$el.find(this.options.addItemButton);
            this.$itemsContainer    = this.$el.find(this.options.itemsContainer);

            this.$el
                .on('change', this.options.productSelect, _.bind(this.onProductChanged, this))
                .on('content:changed', _.bind(this.onContentChanged, this))
            ;

            this.checkAddButton();

            this.initSelects();
        },

        checkAddButton: function() {
            this.$addItemButton.toggle(Boolean(this.$productSelect.val()));
        },

        initSelects: function() {
            this.$el.find(this.options.unitsSelect).addClass(this.options.syncClass);
        },

        /**
         * Handle change
         *
         * @param {jQuery.Event} e
         */
        onProductChanged: function(e) {
            this.checkAddButton();

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
                var select = $(widget).find(self.options.unitsSelect);

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

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();

            RequestProductItemsComponent.__super__.dispose.call(this);
        }
    });

    return RequestProductItemsComponent;
});
