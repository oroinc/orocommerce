define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const routing = require('routing');
    const __ = require('orotranslation/js/translator');

    /**
     * @export oroshipping/js/app/components/product-shipping-freight-classes-component
     * @extends oroui.app.components.base.Component
     * @class oroshipping.app.components.ProductShippingFreightClassesComponent
     */
    const ProductShippingFreightClassesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            routeFreightClassUpdate: 'oro_shipping_freight_classes',
            errorMessage: 'Sorry, an unexpected error has occurred.',
            triggerTimeout: 1500,
            activeUnitCodeParam: 'activeUnitCode',
            excludeFields: ['descriptions', 'shortDescriptions', 'prices'],
            excludeFilter: ':not([name^="oro_product[{{name}}]"])',
            selectors: {
                itemContainer: 'tr.list-item',
                unitSelect: 'select[name^="oro_product[product_shipping_options]"][name$="[productUnit]"]',
                freightClassSelector: '.freight-class-select',
                freightClassUpdateSelector: 'input, select:not(".freight-class-select")'
            }
        },

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @property {jQuery}
         */
        $freightClassesSelect: null,

        /**
         * @property {Number}
         */
        timeoutId: null,

        /**
         * @inheritdoc
         */
        constructor: function ProductShippingFreightClassesComponent(options) {
            ProductShippingFreightClassesComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initializeListener();
        },

        initializeListener: function() {
            this.listenerOff();
            this.listenerOn();
        },

        listenerOff: function() {
            this.options._sourceElement
                .off('change', this.options.selectors.freightClassUpdateSelector)
                .off('keyup', this.options.selectors.freightClassUpdateSelector);
        },

        listenerOn: function() {
            const callback = this.callEntryPoint.bind(this);

            const changeCallback = e => {
                if (this.timeoutId || $(e.target).is('select')) {
                    callback.call(this, e);
                }

                this.clearTimeout();
            };

            const keyUpCallback = e => {
                this.clearTimeout();

                this.timeoutId = setTimeout(callback.bind(this, e), this.options.triggerTimeout);
            };

            this.options._sourceElement
                .on('change', this.options.selectors.freightClassUpdateSelector, changeCallback)
                .on('keyup', this.options.selectors.freightClassUpdateSelector, keyUpCallback);
        },

        clearTimeout: function() {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);

                this.timeoutId = null;
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        callEntryPoint: function(e) {
            const $itemContainer = $(e.target).closest(this.options.selectors.itemContainer);

            let inputsSelector = ':input[data-ftid]';
            _.each(this.options.excludeFields, function(field) {
                inputsSelector += this.options.excludeFilter.replace('{{name}}', field);
            }, this);
            const $formInputs = $itemContainer.closest('form').find(inputsSelector);

            let formData = $formInputs.serialize();

            this.listenerOff();
            this.$freightClassesSelect = $itemContainer.find(this.options.selectors.freightClassSelector);

            if (this.loadingMaskView) {
                this.loadingMaskView.dispose();
            }
            this.loadingMaskView = new LoadingMaskView({container: this.$freightClassesSelect.closest('td')});

            formData = formData + '&' + this.options.activeUnitCodeParam + '=' +
                encodeURI($itemContainer.find(this.options.selectors.unitSelect).val());
            $.ajax({
                url: routing.generate(this.options.routeFreightClassUpdate),
                type: 'post',
                data: formData,
                beforeSend: this._beforeSend.bind(this),
                success: this._success.bind(this),
                complete: this._complete.bind(this),
                errorHandlerMessage: __(this.options.errorMessage),
                error: this._dropValues.bind(this)
            });
        },

        /**
         * @private
         *
         * @param {Boolean} disabled
         */
        _dropValues: function(disabled) {
            this.$freightClassesSelect
                .prop('disabled', disabled)
                .val(null)
                .find('option:not([value=""])')
                .remove();
        },

        /**
         * @private
         */
        _beforeSend: function() {
            if (this.loadingMaskView) {
                this.loadingMaskView.show();
            }
        },

        /**
         * @param {Object} data
         *
         * @private
         */
        _success: function(data) {
            const self = this;
            const units = data.units;
            const disabled = _.isEmpty(units);
            const value = this.$freightClassesSelect.val();
            this._dropValues(disabled);
            if (!_.isEmpty(units)) {
                $.each(units, function(code, label) {
                    if (!self.$freightClassesSelect.find('option[value=' + code + ']').length) {
                        self.$freightClassesSelect.append($('<option/>').val(code).text(label));
                    }
                });
                this.$freightClassesSelect.val(value).change();

                if (!this.$freightClassesSelect.val()) {
                    this.$freightClassesSelect.val(_.keys(units)[0]);
                }
            } else {
                this.$freightClassesSelect.val('');
            }
        },

        /**
         * @private
         */
        _complete: function() {
            this.$freightClassesSelect
                .trigger('value:changed')
                .trigger('change');
            if (this.loadingMaskView) {
                this.loadingMaskView.hide();
            }
            this.clearTimeout();
            this.listenerOn();
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.listenerOff();

            ProductShippingFreightClassesComponent.__super__.dispose.call(this);
        }
    });

    return ProductShippingFreightClassesComponent;
});
