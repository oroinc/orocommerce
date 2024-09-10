import {macros} from 'underscore';
import unitsUtil from 'oroproduct/js/app/units-util';

macros('oroproduct', {
    /**
     * Renders units as radio group
     *
     * @param {Object} data
     * @param {Object} data.units
     * @param {string} data.selectedValue
     */
    renderUnitsAsRadioGroup: require('tpl-loader!oroproduct/templates/units-as-radio-group.html'),

    /**
     * Determines to show units as a radio group
     * @param {Object} units
     * @returns {boolean}
     */
    displayUnitsAsGroup(units) {
        return unitsUtil.displayUnitsAsGroup(units);
    },

    /**
     * Determines whether units are in single mode
     * @param {Object} units
     * @returns {boolean}
     */
    isSingleUnitMode(units) {
        return unitsUtil.isSingleUnitMode(units);
    }
});
