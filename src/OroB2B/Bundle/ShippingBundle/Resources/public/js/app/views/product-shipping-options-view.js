/*jslint nomen:true*/
/*global define*/
define(function(require) {
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
            selectSelector: 'select[name^="orob2b_product[product_shipping_options]"][name$="[productUnit]"]',
            selectors: {
                itemsContainer: 'table.list-items',
                itemContainer: 'table tr.list-item',
                addButtonSelector: '.product-shipping-options-collection .add-list-item'
            }
        },

        listen: {
            'product:precision:remove mediator': 'onContentChanged',
            'product:precision:add mediator': 'onContentChanged'
        },

        /**
         * @property {jQuery}
         */
        $itemsContainer: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {
            this.$itemsContainer = this.$el.find(this.options.selectors.itemsContainer);

            this.$el
                .on('content:changed', _.bind(this.onContentChanged, this))
                .on('content:remove', _.bind(this.onContentRemoved, this))
            ;
            this.$itemsContainer.on('click', '.removeRow', _.bind(this.onRemoveRowClick, this));

            this.onContentChanged();
        },

        onContentChanged: function() {
            var items = this.$el.find(this.options.selectors.itemContainer);
            var self = this;
            var productUnits = this.getProductUnits();
            var selectedUnits = [];
            var allSelectedUnits = [];

            _.each(this.getSelects(), function(select) {
                var currentValue = $(select).val();
                if (_.indexOf(_.keys(productUnits), currentValue) !== -1) {
                    allSelectedUnits.push(currentValue);
                }
            });

            _.each(this.getSelects(), function(select) {
                var $select = $(select);
                var currentValue = $select.val();
                var clearChangeRequired = self.clearOptions(productUnits, $select);
                var addChangeRequired = self.addOptions(productUnits, allSelectedUnits, currentValue, $select);
                allSelectedUnits.push($select.val());

                if (clearChangeRequired || addChangeRequired) {
                    $select.trigger('change');
                }
                if (_.indexOf(selectedUnits, $select.val()) === -1) {
                    selectedUnits.push($select.val());
                }
            });

            this.$itemsContainer.toggle(items.length > 0);

            $(this.options.selectors.addButtonSelector).toggle(
                _.keys(productUnits).length > selectedUnits.length
            );
        },

        onContentRemoved: function() {
            var items = this.$el.find(this.options.selectors.itemContainer);

            if (items.length === 0) {
                this.$itemsContainer.hide();
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        onRemoveRowClick: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(e.target).closest('*[data-content]')
                .trigger('content:remove')
                .remove();
            this.onContentChanged();
        },

        /**
         * Return units from data attribute of Product
         *
         * @returns {Object}
         */
        getProductUnits: function() {
            return $(':data(' + this.options.unitsAttribute + ')').data(this.options.unitsAttribute) || {};
        },

        /**
         * Return selects to update
         *
         * @returns {jQuery.Element}
         */
        getSelects: function() {
            return this.$itemsContainer.find(this.options.selectSelector);
        },

        /**
         * Clear options from selects
         *
         * @param {Array} units
         * @param {jQuery.Element} $select
         *
         * @return {Boolean}
         */
        clearOptions: function(units, $select) {
            var updateRequired = false;

            _.each($select.find('option'), function(option) {
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
         * @param {Array} allSelectedUnits
         * @param {String} currentValue
         * @param {jQuery.Element} $select
         *
         * @return {Boolean}
         */
        addOptions: function(units, allSelectedUnits, currentValue, $select) {
            var updateRequired = false;

            // if current value was removed
            var needUpdateValue = (_.indexOf(_.keys(units), currentValue) === -1);
            _.each(units, function(text, value) {
                if (!$select.find('option[value="' + value + '"]').length) {
                    $select.append($('<option/>').val(value).text(text));
                    updateRequired = true;
                }
                if (needUpdateValue && (_.indexOf(allSelectedUnits, value) === -1)) {
                    $select.val(value);
                    needUpdateValue = false;
                }
            });

            // if no value found
            if (needUpdateValue) {
                $select.append($('<option/>').val('').text('')).val('');
            }

            return updateRequired;
        }
    });
});
