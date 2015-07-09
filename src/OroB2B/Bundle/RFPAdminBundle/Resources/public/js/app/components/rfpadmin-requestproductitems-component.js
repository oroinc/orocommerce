/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var RequestProductItemsComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        routing = require('routing'),
        messenger = require('oroui/js/messenger');

    RequestProductItemsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            productSelect:  '.rfpadmin-requestproduct-product-select input[type="hidden"]',
            unitsSelect:    '.rfpadmin-requestproductitem-productunit-select',
            unitsRoute:     'orob2b_product_unit_product_units',
            addItemButton:  '.add-list-item',
            itemsContainer: '.rfpadmin-requestproductitem-collection .oro-item-collection',
            itemWidget:     '.rfpadmin-requestproductitem-widget',
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
        $container : null,

        /**
         * @property {Object}
         */
        $productSelect : null,

        /**
         * @property {Object}
         */
        $addItemButton : null,

        /**
         * @property {Object}
         */
        $itemsContainer : null,

        /**
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options    = _.defaults(options || {}, this.options);
            this.units      = _.defaults(this.units, options.units);

            this.$container = options._sourceElement;

            this.loadingMask = new LoadingMaskView({container: this.$container});

            this.$productSelect     = this.$container.find(this.options.productSelect);
            this.$addItemButton     = this.$container.find(this.options.addItemButton);
            this.$itemsContainer    = this.$container.find(this.options.itemsContainer);

            this.$container
                .on('change', this.options.productSelect, _.bind(this.onProductChanged, this))
                .on('content:changed', _.bind(this.onContentChanged, this))
            ;

            this.checkAddButton();

            this.initSelects();
        },

        checkAddButton: function () {
            this.$addItemButton.toggle(Boolean(this.$productSelect.val()));
        },

        initSelects: function () {
            this.$container.find(this.options.unitsSelect).addClass(this.options.syncClass);
        },

        /**
         * Handle change
         *
         * @param {jQuery.Event} e
         */
        onProductChanged: function (e) {
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
        onContentChanged: function (e) {
            this.updateContent(false);
        },

        /**
         * @param {Boolean} force
         */
        updateContent: function (force) {
            var productId = this.$productSelect.val();
            var productUnits = this.units[productId];

            if (!productId || productUnits) {
                this.updateProductUnits(productUnits, force || false);
            } else {
                var self = this;
                $.ajax({
                    url: routing.generate(this.options.unitsRoute, {'id': productId}),
                    type: 'GET',
                    beforeSend: function () {
                        self.loadingMask.show();
                    },
                    success: function (response) {
                        self.units[productId] = response.units;
                        self.updateProductUnits(response.units, true);
                    },
                    complete: function () {
                        self.loadingMask.hide();
                    },
                    error: function (xhr) {
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
        updateProductUnits: function (data, force) {
            var self = this;

            var units = data || {};

            var widgets = self.$container.find(self.options.itemWidget);

            $.each(widgets, function (index, widget) {
                var select = $(widget).find(self.options.unitsSelect);

                if (!force && $(select).hasClass(self.options.syncClass)) {
                    return;
                }

                var currentValue = $(select).val();
                $(select).empty();
                $.each(units, function (key, value) {
                    $(select)
                        .append($('<option/>').val(key).text(value))
                    ;
                });
                if (!currentValue && $(select).has('option:first-child')) {
                    currentValue = $(select).find('option:first-child').val();
                }
                $(select).val(currentValue);
                $(select).addClass(self.options.syncClass);

                if (!force) {
                    $(widget).find('select').uniform('update');
                }
            });

            if (force) {
                this.$container.find('select').uniform('update');
            }
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.$container.off();

            RequestProductItemsComponent.__super__.dispose.call(this);
        }
    });

    return RequestProductItemsComponent;
});
