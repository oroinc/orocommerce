define(function(require) {
    'use strict';

    var UnitsUtil;
    var _ = require('underscore');

    UnitsUtil = {
        getUnitsLabel: function(model) {
            var units = {};
            var unitLabel = model.get('unit_label_template') || 'oro.product.product_unit.%s.label.full';
            _.each(model.get('product_units'), function(precision, value) {
                units[value] = _.__(unitLabel.replace('%s', value));
            });
            return units;
        },

        updateSelect: function(model, $el) {
            var options = [];
            var oldValue = $el.val();
            var units = this.getUnitsLabel(model);
            if (!_.isEmpty(units)) {
                _.each(units, function(label, value) {
                    options.push(this.generateSelectOption(value, label));
                }, this);

                $el.prop('disabled', false);
                $el.prop('readonly', options.length <= 1);
            } else {
                if (!model.has('unit_placeholder')) {
                    model.set('unit_placeholder', $el.find('option[value=""]').text() || '');
                }
                options.push(this.generateSelectOption('', model.get('unit_placeholder')));

                $el.prop('disabled', true);
            }

            $el.html(options.join(''));

            var value = model.get('unit_deferred') || oldValue;
            if (!value || !$el.find('option[value="' + _.escape(value) + '"]').length) {
                value = $el.val();
            }

            model.set('unit', value);
            if (value !== oldValue || value !== $el.val()) {
                $el.val(value).change();
            }
            $el.inputWidget('refresh');
        },

        generateSelectOption: function(value, label) {
            return '<option value="' + _.escape(value) + '">' + _.escape(label) + '</option>';
        }
    };

    return UnitsUtil;
});
