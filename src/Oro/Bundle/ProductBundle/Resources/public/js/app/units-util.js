define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const InputWidgetManager = require('oroui/js/input-widget-manager');

    const UnitsUtil = {
        /**
         * An attribute name used to differentiate elements as they are unit-select widget
         */
        UNIT_SELECT_NAME: 'data-toggle-type',

        UNIT_SELECT_TYPE: {
            SINGLE: 'single',
            TOGGLE: 'toggle',
            SELECT: 'select'
        },

        /**
         * An exact count of units to render them as a radio group
         */
        UNIT_COUNT_AS_GROUP: 2,

        /**
         * A maximum length of unit name to render it as a radio group
         */
        UNIT_LENGTH_AS_GROUP: 6,

        /**
         * Determines to show units as a radio group
         *
         * @param {Object} units
         * @param {string} [type]
         * @returns {boolean}
         */
        displayUnitsAsGroup(units, type = 'full') {
            if (!units) {
                return false;
            }

            return Object.keys(units).length === this.UNIT_COUNT_AS_GROUP &&
                _.every(units, (label, key) => {
                    if (typeof label === 'string') {
                        return label.length <= this.UNIT_LENGTH_AS_GROUP;
                    }

                    if (_.isObject(label)) {
                        const unitLabel = __(
                            `oro.product.product_unit.${key}.value.${type}`, {count: ''}, 1
                        ) ?? '';
                        return unitLabel.length && unitLabel.length <= this.UNIT_LENGTH_AS_GROUP;
                    }
                    return false;
                });
        },

        markAsSingleUnit(el) {
            $(el).attr(this.UNIT_SELECT_NAME, this.UNIT_SELECT_TYPE.SINGLE);
        },

        markAsSelectUnit(el) {
            $(el).attr(this.UNIT_SELECT_NAME, this.UNIT_SELECT_TYPE.SELECT);
        },

        markAsToggleUnit(el) {
            $(el).attr(this.UNIT_SELECT_NAME, this.UNIT_SELECT_TYPE.TOGGLE);
        },

        /**
         * Determines whether units are in single mode
         * @param {Object} units
         * @returns {boolean}
         */
        isSingleUnitMode(units) {
            return Object.keys(units).length === 1;
        },

        getUnitLabel: function(model, unitCode) {
            const translationKey = model.get('unit_label_template') || 'oro.product.product_unit.%s.label.full';
            return __(translationKey.replace('%s', unitCode));
        },

        getUnitsLabel: function(model) {
            const units = {};
            _.each(model.get('product_units') || model.get('units'), function(precision, unitCode) {
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
                    $el.trigger('change');
                }
            }

            if (InputWidgetManager.hasWidget($el)) {
                $el.inputWidget('refresh');
            }
        },

        generateSelectOption: function(value, label) {
            return `<option value="${_.escape(value)}">${_.escape(label)}</option>`;
        }
    };

    return UnitsUtil;
});
