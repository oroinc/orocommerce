define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    return BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            unitsAttribute: 'units',
            selectSelector: "select[name^='orob2b_product[product_shipping_options]'][name$='[productUnit]']",
            selectors     : {
                itemsContainer: 'table.list-items',
                itemContainer : 'table tr.list-item'
            }
        },

        listen: {
            'product:precision:remove mediator': 'onContentChanged',
            'product:precision:add mediator'   : 'onContentChanged'
        },

        /**
         * @property {jQuery}
         */
        $itemsContainer: null,

        /**
         * @inheritdoc
         */
        initialize: function (options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {
            this.$itemsContainer = this.$el.find(this.options.selectors.itemsContainer);

            this.$el
                .on('content:changed', _.bind(this.onContentChanged, this))
                .on('content:remove', _.bind(this.onContentRemoved, this))
            ;

            this.onContentChanged();
        },

        onContentChanged: function () {
            var items = this.$el.find(this.options.selectors.itemContainer);

            if (items.length > 0) {
                this.$itemsContainer.show();
            }
            var self = this,
                productUnits = this.getProductUnits()
                ;

            _.each(this.getSelects(), function (select) {
                var $select = $(select);
                var clearChangeRequired = self.clearOptions(productUnits, $select);
                var addChangeRequired = self.addOptions(productUnits, $select);
                if (clearChangeRequired || addChangeRequired) {
                    $select.trigger('change');
                }
            });

            if (items.length > 0) {
                this.$itemsContainer.show();
            }
        },

        onContentRemoved: function () {
            var items = this.$el.find(this.options.selectors.itemContainer);

            if (items.length <= 1) {
                this.$itemsContainer.hide();
            }
        },

        /**
         * Return units from data attribute of Product
         *
         * @returns {Object}
         */
        getProductUnits: function () {
            return $(':data(' + this.options.unitsAttribute + ')').data(this.options.unitsAttribute) || {};
        },

        /**
         * Return selects to update
         *
         * @returns {jQuery.Element}
         */
        getSelects: function () {
            return this.itemsContainer.find(this.options.selectSelector);
        },

        /**
         * Clear options from selects
         *
         * @param {Array} units
         * @param {jQuery.Element} $select
         *
         * @return {Boolean}
         */
        clearOptions: function (units, $select) {
            var updateRequired = false;

            _.each($select.find('option'), function (option) {

                if (!option.value) {
                    return;
                }

                if (!units.hasOwnProperty(option.value)) {
                    option.remove();

                    updateRequired = true;

                    return;
                }
                var $option = $(option);
                if (!$option.is(':selected')) {
                    option.remove();

                    updateRequired = true;
                }
            });

            return updateRequired;
        },

        /**
         * Add options based on units configuration
         *
         * @param {Array} units
         * @param {jQuery.Element} $select
         *
         * @return {Boolean}
         */
        addOptions: function (units, $select) {
            var updateRequired = false,
                emptyOption = $select.find('option[value=""]');

            if (_.isEmpty(units)) {
                emptyOption.show();
            } else {
                emptyOption.hide();
            }

            _.each(units, function (text, value) {
                if (!$select.find("option[value='" + value + "']").length) {
                    $select.append('<option value="' + value + '">' + text + '</option>');
                    updateRequired = true;
                }
            });

            if ($select.val() == '' && !_.isEmpty(units)) {
                var value = _.keys(units)[0];
                $select.val(value);
                updateRequired = true;
            }

            return updateRequired;
        }
    });
});
