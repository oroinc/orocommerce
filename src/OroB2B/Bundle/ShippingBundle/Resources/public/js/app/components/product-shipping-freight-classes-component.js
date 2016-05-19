/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ProductShippingFreightClassesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var routing = require('routing');
    var messenger =  require('oroui/js/messenger');
    var __ = require('orotranslation/js/translator');

    /**
     * @export orob2bshipping/js/app/components/product-shipping-freight-classes-component
     * @extends oroui.app.components.base.Component
     * @class orob2bshipping.app.components.ProductShippingFreightClassesComponent
     */
    ProductShippingFreightClassesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            routeFreightClassUpdate: 'orob2b_shipping_freight_classes',
            errorMessage: 'Sorry, unexpected error was occurred',
            triggerTimeout: 1500,
            activeUnitCodeParam: 'activeUnitCode',
            excludeFields: ['descriptions', 'shortDescriptions', 'prices'],
            excludeFilter: ':not([name^="orob2b_product[{{name}}]"])',
            selectors: {
                itemContainer: 'tr.list-item',
                unitSelect: 'select[name^="orob2b_product[product_shipping_options]"][name$="[productUnit]"]',
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
         * @inheritDoc
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
            var callback = _.bind(this.callEntryPoint, this);

            var changeCallback = _.bind(function(e) {
                if (this.timeoutId || $(e.target).is('select')) {
                    callback.call(this, e);
                }

                this.clearTimeout();
            }, this);

            var keyUpCallback = _.bind(function(e) {
                this.clearTimeout();

                this.timeoutId = setTimeout(_.bind(callback, this, e), this.options.triggerTimeout);
            }, this);

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
            var self = this;
            var $itemContainer = $(e.target).closest(this.options.selectors.itemContainer);

            var inputsSelector = ':input[data-ftid]';
            _.each(this.options.excludeFields, function(field) {
                inputsSelector += self.options.excludeFilter.replace('{{name}}', field);
            });
            var $formInputs = $itemContainer.closest('form').find(inputsSelector);

            var formData = $formInputs.serialize();

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
                beforeSend: $.proxy(this._beforeSend, this),
                success: $.proxy(this._success, this),
                complete: $.proxy(this._complete, this),
                error: $.proxy(function(jqXHR) {
                    this._dropValues(true);
                    messenger.showErrorMessage(__(self.options.errorMessage), jqXHR.responseJSON);
                }, this)
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
            var self = this;
            var units = data.units;
            var disabled = _.isEmpty(units);
            var value = this.$freightClassesSelect.val();
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
