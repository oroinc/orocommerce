define(function(require) {
    'use strict';

    var LineItemOfferView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var LineItemProductView = require('oroproduct/js/app/views/line-item-product-view');

    LineItemOfferView = LineItemProductView.extend({
        /**
         * @property {Object}
         */
        options: {
            'allUnits': [],
            $: {
                product: ''
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            mediator.on('product:unit:filter-values', _.bind(this.filterUnits, this));
            this.elements.id = $(options.$.product);
            this.options = $.extend(true, {}, this.options, options || {});
            _.each(this.options.$, _.bind(function(selector, field) {
                this.options.$[field] = $(selector);
            }, this));

            LineItemOfferView.__super__.initialize.apply(this, arguments);

            // get all units
            _.each(this.getElement('unit').find('option'), _.bind(function(elem) {
                this.options.allUnits[elem.value] = elem.text;
            }, this));
        },

        /**
         * @param {Array} units
         */
        filterUnits: function(productId, units) {
            if (this.model.get('id') !== productId) {
                return;
            }
            var self = this;
            var $select = this.getElement('unit');
            var value = $select.val();

            $select
                .val(null)
                .find('option')
                .remove();

            if (units) {
                _.each(self.options.allUnits, function(code, label) {
                    if ($select.find('option[value=' + code + ']').length ||
                        (-1 === $.inArray(this.value, units))
                    ) {
                        return;
                    }
                    $select.append($('<option/>').val(code).text(label));
                });
                $select.val(value);
                if ($select.val() === null) {
                    $select.val(units[0]);
                }
            }

            $select
                .trigger('value:changed')
                .trigger('change');
        }
    });

    return LineItemOfferView;
});
