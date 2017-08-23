define(function(require) {
    'use strict';

    var UnitsUtil;
    var _ = require('underscore');

    UnitsUtil = {
        updateSelect: function(model, $el, units) {
            var options = [];
            units = units || model.get('product_units');
            var oldValue = $el.val();
            if (!_.isEmpty(units)) {
                _.each(units, function(label, value) {
                    options.push(this.generateSelectOption(value, label));
                }, this);

                $el.prop('disabled', false);
            } else {
                if (!model.has('unit_placeholder')) {
                    model.set('unit_placeholder', $el.find('option[value=""]').text() || '');
                }
                options.push(this.generateSelectOption('', model.get('unit_placeholder')));

                $el.prop('disabled', true);
            }

            $el.html(options.join(''));

            var value = model.get('unit_deferred') || oldValue;
            if (!value || !$el.find('option[value="' + value + '"]').length) {
                value = $el.val();
            }

            model.set('unit', value);
            if (value !== oldValue || value !== $el.val()) {
                $el.val(value).change();
            }
            $el.inputWidget('refresh');
        },

        generateSelectOption: function(value, label) {
            return '<option value="' + value + '">' + label + '</option>';
        }
    };

    return UnitsUtil;
});
