/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ProductUnitSelectComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductUnitSelectComponent = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            unitLabel: 'orob2b.product.product_unit.%s.label.full'
        },

        /**
         * @param {Object} additionalOptions
         */
        initialize: function(additionalOptions) {
            _.extend(this.options, additionalOptions || {});

            this.initSelect();
        },

        initSelect: function() {
            var model = this.options.productModel || null;
            if (!model) {
                return;
            }
            var productUnits = model.get('product_units');
            var select = this.options._sourceElement.find('select');
            select.empty();
            for (var i = 0; i < productUnits.length; i++) {
                var unitCode = productUnits[i];
                var unitValue = _.__(this.options.unitLabel.replace('%s', unitCode));
                select.append($('<option></option>').attr('value', unitCode).text(unitValue));
            }
            select.change();
        }
    });

    return ProductUnitSelectComponent;
});
