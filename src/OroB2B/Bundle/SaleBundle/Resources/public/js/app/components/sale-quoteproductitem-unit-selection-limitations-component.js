/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var QuoteProductItemUnitSelectionLimitationsComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        routing = require('routing'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        BaseComponent = require('oroui/js/app/components/base/component');

    QuoteProductItemUnitSelectionLimitationsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        $container : null,

        /**
         * @property {Object}
         */
        $productSelect : null,

        /** @property {LoadingMaskView|null} */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var containerId = options['containerId'];
            if (!containerId) {
                return;
            }

            this.$container = $(containerId);

            // reassign Add button onclick - ToDo: wait for merge #BB-569
            $(document).off('click', '.add-list-item');
            $(document).on('click', '.add-list-item', _.bind(this.onAddClick, this));

            this.$productSelect = this.$container
                .closest('.sale-quoteproduct-widget')
                .find('select.sale-quoteproduct-product-select');
            this.$productSelect.on('change', _.bind(this.onChange, this));
            this.$container.on('content:changed', _.bind(this.onContentChange, this));

            this.loadingMask = new LoadingMaskView({
                container: this.$container
            });
        },

        /**
         * Handle change select
         *
         * @param {jQuery.Event} e
         */
        onChange: function (e) {
            var self = this;
            var productId = self.$productSelect.val();
            self.loadingMask.show();
            $.get(routing.generate('orob2b_api_get_product_available_units', {'id': productId}))
                .done(_.bind(this.updateProductUnits, this));
        },

        /**
         * Handle container content change
         *
         * @param {jQuery.Event} e
         */
        onContentChange: function (e) {
            var allowedUnitsData = this.$container.data('allowedUnitsData');
            if (allowedUnitsData) {
                this.updateProductUnits(allowedUnitsData, false);
            } else {
                this.$productSelect.trigger('change');
            }
        },

        /**
         * Handle add button click
         *
         * @param {jQuery.Event} e
         */
        onAddClick: function (e) {
            var self = this;
            var target = e.srcElement || e.target;
            e.preventDefault();
            var $listContainer, index, html, placeholder, placeholderRegexp;
            placeholder = $(target).data('prototype-name') || '__name__';
            placeholderRegexp =  new RegExp(self.escapeRegExp(placeholder), 'g');
            $listContainer = $(target).siblings('.collection-fields-list');
            index = $listContainer.data('last-index') || $listContainer.children().length;

            html = $listContainer.attr('data-prototype').replace(placeholderRegexp, index);
            $listContainer.append(html)
                .trigger('content:changed')
                .data('last-index', index + 1);
        },

        /**
         * Update available ProductUnit select
         *
         * @param {Object} data
         * @param {Boolean} afterLoad
         */
        updateProductUnits: function(data, afterLoad) {
            var self = this;
            if (!data.successful) {
                return;
            }
            var newOptions = data.data;
            var selects = self.$container.find('select.sale-quoteproductitem-productunit-select');

            $.each(selects, function(index, select) {
                var currentValue = $(select).val();
                $(select).empty();
                $.each(newOptions, function(key, value) {
                    $(select)
                        .append($('<option></option>')
                        .attr('value', key).text(value))
                    ;
                });
                $(select).val(currentValue);
                $(select).uniform('update');
            });
            if (afterLoad) {
                self.$container.data('allowedUnitsData', data);
                self.loadingMask.hide();
            }
        },
        /**
         * Escape string for using in regexp
         *
         * @param {String} s
         */
        escapeRegExp: function(s){
            return s.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
        }
    });

    return QuoteProductItemUnitSelectionLimitationsComponent;
});
