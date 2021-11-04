define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const LineItemProductView = require('oroproduct/js/app/views/line-item-product-view');

    const LineItemOfferView = LineItemProductView.extend({
        /**
         * @property {Object}
         */
        options: {
            allUnits: [],
            $: {
                product: ''
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function LineItemOfferView(options) {
            LineItemOfferView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.elementsEvents.unit = ['product-units:change', 'onProductUnitsChange'];
            this.elements.id = $(options.$.product);
            this.options = $.extend(true, {}, this.options, options || {});
            _.each(this.options.$, _.bind(function(selector, field) {
                this.options.$[field] = $(selector);
            }, this));

            LineItemOfferView.__super__.initialize.call(this, options);

            // get all units
            _.each(this.getElement('unit').find('option'), _.bind(function(elem) {
                this.options.allUnits.push({code: elem.value, label: elem.text});
            }, this));
            this.model.on('product:unit:filter-values', _.bind(this.filterUnits, this));
            this.initializeProductUnits();
        },

        onProductUnitsChange: function() {
            this.model.set('product_units', this.getElement('unit').data('product-units'));
            LineItemOfferView.__super__.onProductUnitsChange.call(this);
        },

        /**
         * @param {Array} units
         */
        filterUnits: function(units) {
            const $select = this.getElement('unit');
            const value = $select.val();

            $select
                .val(null)
                .find('option')
                .remove();

            if (units) {
                _.each(this.options.allUnits, _.bind(function(unit) {
                    if (-1 !== $.inArray(unit.code, units)) {
                        $select.append($('<option/>').val(unit.code).text(unit.label));
                    }
                }));
                $select.val(value);
                if ($select.val() === null) {
                    $select.val(units[0]);
                }
            }

            $select
                .trigger('value:changed')
                .trigger('change');
        },

        /**
         * Set units value to model if it has been already initialized.
         */
        initializeProductUnits: function() {
            const units = this.getElement('unit').data('product-units');
            if (units !== undefined) {
                this.model.set('product_units', units);
            }
        }
    });

    return LineItemOfferView;
});
