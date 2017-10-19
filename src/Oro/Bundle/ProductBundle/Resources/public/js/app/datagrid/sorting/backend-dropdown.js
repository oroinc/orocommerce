define(function(require) {
    'use strict';

    var BackendSortingDropdown;
    var SortingDropdown = require('orodatagrid/js/datagrid/sorting/dropdown');

    BackendSortingDropdown = SortingDropdown.extend({
        /** @property */
        hasSortingOrderButton: false,

        /** @property */
        className: '',

        /** @property */
        dropdownClassName: 'oro-select2__dropdown',

        /** @property */
        template: require('tpl!oroproduct/templates/datagrid/sorting-dropdown.html'),

        /** @property */
        themeOptions: {
            optionPrefix: 'backendsortingdropdown',
            el: '[data-grid-sorting]'
        }
    });

    return BackendSortingDropdown;
});
