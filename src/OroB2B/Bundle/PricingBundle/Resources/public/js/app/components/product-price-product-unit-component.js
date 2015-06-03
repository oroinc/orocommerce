/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ProductPriceProductUnitComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        routing = require('routing'),
        messenger = require('oroui/js/messenger'),
        __ = require('orotranslation/js/translator');

    ProductPriceProductUnitComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            productSelector: '.price-product-product',
            quantitySelector: '.price-product-quantity input',
            unitSelector: '.price-product-unit select',
            router: 'orob2b_product_unit_product_units',
            routingParams: {},
            errorMessage: 'Sorry, unexpected error was occurred'
        },

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});

            this.options._sourceElement
                .on('change', this.options.productSelector, _.bind(this.onProductChange, this));

            this.options._sourceElement.find(this.options.productSelector).trigger('change');
        },

        /**
         * @param {jQuery.Event} e
         */
        onProductChange: function (e) {
            var value = e.target.value,
                self = this;

            if (!value) {
                return this._dropValues();
            }

            var routeParams = $.extend({}, this.options.routingParams, {'id': value});
            $.ajax({
                url: routing.generate(this.options.router, routeParams),
                beforeSend: $.proxy(this._beforeSend, this),
                success: $.proxy(this._success, this),
                complete: $.proxy(this._complete, this),
                error: function (jqXHR) {
                    messenger.showErrorMessage(__(self.options.errorMessage), jqXHR.responseJSON);
                }
            });
        },

        /**
         * @private
         */
        _beforeSend: function () {
            this.loadingMaskView.show();
            this._dropValues();
        },

        /**
         * @private
         */
        _dropValues: function () {
            this.handleQuantityState(true);
            this.handleUnitsState(true, null);
        },

        /**
         * @param {Object} data
         *
         * @private
         */
        _success: function (data) {
            this.handleQuantityState(false);
            this.handleUnitsState(false, data.units);
        },

        /**
         * @private
         */
        _complete: function () {
            this.loadingMaskView.hide();
        },

        /**
         * @param {Boolean} disabled
         */
        handleQuantityState: function (disabled) {
            this.options._sourceElement.find(this.options.quantitySelector).prop('disabled', disabled).val(null);
        },

        /**
         * @param {Boolean} disabled
         * @param {Object} units
         */
        handleUnitsState: function (disabled, units) {
            var unitSelector = this.options._sourceElement.find(this.options.unitSelector);

            unitSelector
                .prop('disabled', disabled)
                .val(null)
                .find('option')
                .filter(function () {
                    return this.value || $.trim(this.value).length != 0;
                })
                .remove();

            if (units) {
                $.each(units, function (code, label) {
                    if (!unitSelector.find("option[value='" + code + "']").length) {
                        unitSelector.append($('<option></option>').val(code).text(label));
                    }
                });
            }

            unitSelector.trigger('change');

            if (disabled) {
                unitSelector.parent('.selector').addClass('disabled');
            } else {
                unitSelector.parent('.selector').removeClass('disabled');
            }
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            ProductPriceProductUnitComponent.__super__.dispose.call(this);
        }
    });

    return ProductPriceProductUnitComponent;
});
