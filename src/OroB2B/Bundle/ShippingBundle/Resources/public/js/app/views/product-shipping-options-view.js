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
                addButtonSelector: '.product-shipping-options-collection .add-list-item',
                subselects: '.shipping-weight select, .shipping-dimensions select, .shipping-freight-class select'
            }
        },

        /**
         * @property {Object}
         */
        listen: {
            'product:precision:remove mediator': 'onPrecisionRemoved',
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
                .on('content:changed', _.bind(this.onContentChanged, this));

            this.$itemsContainer
                .on('click', '.removeRow', _.bind(this.onRemoveRowClick, this))
                .on('change', this.options.selectSelector, _.bind(this.onContentChanged, this));

            this.$el.find(this.options.selectors.subselects).data('selected', true);

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

                self.changeOptions(productUnits, allSelectedUnits, $select);

                if (_.indexOf(selectedUnits, $select.val()) === -1) {
                    selectedUnits.push($select.val());
                }
            });

            _.each(this.$el.find(this.options.selectors.subselects), function(select) {
                var $first = $(select).find('option:not([value=""]):first');
                if (!$(select).data('selected') && !$(select).val() && $first.length) {
                    $(select).val($first.val()).change();
                    $(select).data('selected', true);
                }
            });

            this.$itemsContainer.toggle(items.length > 0);

            $(this.options.selectors.addButtonSelector).toggle(
                _.keys(productUnits).length > selectedUnits.length
            );
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
         * @param {Object} productUnits
         */
        onPrecisionRemoved: function(productUnits) {
            var self = this;

            var selects = this.$el.find(this.options.selectSelector);

            _.each(selects, function(select) {
                if (productUnits.hasOwnProperty($(select).val())) {
                    return;
                }

                $(select).closest(self.options.selectors.itemContainer)
                    .find('.removeRow').trigger('click');
            });

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
         * @param {Array} units
         * @param {Array} allSelectedUnits
         * @param {jQuery.Element} $select
         */
        changeOptions: function(units, allSelectedUnits, $select) {
            var currentValue = $select.val();

            _.each(units, function(text, value) {
                if (!$select.find('option[value="' + value + '"]').length) {
                    $select.append($('<option/>').val(value).text(text));
                }
            });

            _.each($select.find('option'), function(option) {
                if (!units.hasOwnProperty(option.value) || !option.value ||
                        _.indexOf(allSelectedUnits, option.value) !== -1 && option.value !== currentValue) {

                    if (option.value === currentValue) {
                        currentValue = '';
                    }

                    option.remove();
                }
            });

            if (!currentValue) {
                var $firstValue = $select.find('option:first');
                $select.val($firstValue.length ? $firstValue.val() : '');

                if ($firstValue.length) {
                    $select.trigger('change');
                }
            }
        }
    });
});
