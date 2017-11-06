define(function(require) {
    'use strict';

    var BackendSortingDropdown;
    var SortingDropdown = require('orodatagrid/js/datagrid/sorting/dropdown');

    BackendSortingDropdown = SortingDropdown.extend({
        /** @property */
        hasSortingOrderButton: false,

        /** @property */
        className: '',

        /**
         * @inheritDoc
         */
        attributes: {
            'data-grid-sorting': ''
        },

        /** @property */
        dropdownClassName: 'oro-select2__dropdown',

        /** @property */
        template: require('tpl!oroproduct/templates/datagrid/sorting-dropdown.html'),

        /** @property */
        themeOptions: {
            optionPrefix: 'backendsortingdropdown',
            el: '[data-grid-sorting]'
        },

        /**
         * @inheritDoc
         */
        onChangeSorting: function() {
            var obj = {};
            this.collection.trigger('backgrid:checkUnSavedData', obj);

            if (obj.live) {
                BackendSortingDropdown.__super__.onChangeSorting.call(this);
            } else {
                this.render();
            }
        }
    });

    return BackendSortingDropdown;
});
