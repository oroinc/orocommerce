define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');

    const ProductShippingOptionsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            unitsAttribute: 'units',
            selectSelector: 'select[name^="oro_product[product_shipping_options]"][name$="[productUnit]"]',
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
            'product:precision:remove mediator': 'onPrecisionRemoved'
        },

        /**
         * @property {jQuery}
         */
        $itemsContainer: null,

        /**
         * @inheritdoc
         */
        constructor: function ProductShippingOptionsView(options) {
            ProductShippingOptionsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.$itemsContainer = this.$el.find(this.options.selectors.itemsContainer);
            this.initLayout().done(this.handleLayoutInit.bind(this));
        },

        handleLayoutInit: function() {
            this.$el
                .on('content:changed', this.onContentChanged.bind(this));

            mediator.on('product:precision:add', this.onContentChanged, this);

            this.$itemsContainer
                .on('click', '.removeRow', this.onRemoveRowClick.bind(this))
                .on('change', this.options.selectSelector, this.onContentChanged.bind(this));

            this.$el.find(this.options.selectors.subselects).data('selected', true);

            this.onContentChanged();
        },

        onContentChanged: function() {
            const items = this.$el.find(this.options.selectors.itemContainer);
            const self = this;
            const productUnits = this.getProductUnits();
            const selectedUnits = [];
            const allSelectedUnits = [];

            _.each(this.getSelects(), function(select) {
                const currentValue = $(select).val();
                if (_.indexOf(_.keys(productUnits), currentValue) !== -1) {
                    allSelectedUnits.push(currentValue);
                }
            });

            _.each(this.getSelects(), function(select) {
                const $select = $(select);

                self.changeOptions(productUnits, allSelectedUnits, $select);

                if (_.indexOf(selectedUnits, $select.val()) === -1) {
                    selectedUnits.push($select.val());
                }
            });

            _.each(this.$el.find(this.options.selectors.subselects), function(select) {
                const $first = $(select).find('option:not([value=""]):first');
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
            const self = this;

            const selects = this.$el.find(this.options.selectSelector);

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
            let units = {};
            $.each($(':data(' + this.options.unitsAttribute + ')'), (index, element) => {
                const elementUnits = $(element).data(this.options.unitsAttribute) || {};
                units = $.extend(units, elementUnits);
            });

            return units;
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
            let currentValue = $select.val();

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

                    $(option).remove();
                }
            });

            if (!currentValue) {
                const $firstValue = $select.find('option:first');
                $select.val($firstValue.length ? $firstValue.val() : '');

                if ($firstValue.length) {
                    $select.trigger('change');
                }
            }
        }
    });

    return ProductShippingOptionsView;
});
