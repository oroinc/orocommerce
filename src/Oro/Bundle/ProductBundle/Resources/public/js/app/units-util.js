define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const InputWidgetManager = require('oroui/js/input-widget-manager');

    const UnitsUtil = {
        getUnitLabel: function(model, unitCode) {
            const translationKey = model.get('unit_label_template') || 'oro.product.product_unit.%s.label.full';
            return __(translationKey.replace('%s', unitCode));
        },

        getUnitsLabel: function(model) {
            const units = {};
            _.each(model.get('product_units'), function(precision, unitCode) {
                units[unitCode] = UnitsUtil.getUnitLabel(model, unitCode);
            });
            return units;
        },

        updateSelect: function(model, $el, silent = false) {
            const options = [];
            const oldValue = $el.val();
            const units = this.getUnitsLabel(model);
            if (!_.isEmpty(units)) {
                _.each(units, function(label, value) {
                    options.push(this.generateSelectOption(value, label));
                }, this);

                $el.prop('disabled', false);
                $el.prop('readonly', options.length <= 1);
            } else {
                $el.prop('disabled', true);
            }

            let value = oldValue || model.get('unit');
            const wishfulLabel = model.get('unit_label');
            if (_.isEmpty(units) || wishfulLabel && !value) {
                // no units loaded or there's wishful unit label, and it could not be resolved to a unit
                // add placeholder option
                const placeholder = model.get('unit_placeholder') || $el.find('option[value=""]').text() || '';
                options.unshift(this.generateSelectOption('', placeholder));
                value = '';
            } else if (!_.isEmpty(units) && (!value || Object.keys(units).indexOf(value) === -1)) {
                // current unit is not within available units
                value = Object.keys(units)[0];
            }

            $el.html(options.join(''));

            model.set('unit', value);
            if (value !== oldValue || value !== $el.val()) {
                $el.val(value);
                if (!silent) {
                    $el.change();
                }
            }

            if (InputWidgetManager.hasWidget($el)) {
                $el.inputWidget('refresh');
            }
        },

        generateSelectOption: function(value, label) {
            return '<option value="' + _.escape(value) + '">' + _.escape(label) + '</option>';
        }
    };

    return UnitsUtil;
});
