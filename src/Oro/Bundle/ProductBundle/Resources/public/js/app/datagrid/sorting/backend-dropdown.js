define(function(require) {
    'use strict';

    var BackendSortingDropdown;
    var SortingDropdown = require('orodatagrid/js/datagrid/sorting/dropdown');

    BackendSortingDropdown = SortingDropdown.extend({
        /** @property */
        themeOptions: {
            optionPrefix: 'backendsortingdropdown',
            el: '[data-grid-sorting]'
        },

        initialize: function(options) {
            BackendSortingDropdown.__super__.initialize.call(this, options);
        }
    });

    return BackendSortingDropdown;
});
