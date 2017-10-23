define(function(require) {
    'use strict';

    var BackendSortingDropdown;
    var __ = require('orotranslation/js/translator');
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
        },

        /**
         * @inheritDoc
         */
        onChangeSorting: function() {
            var obj = {};
            this.collection.trigger('backgrid:checkUnsavedData', obj);

            if (obj.live) {
                BackendSortingDropdown.__super__.onChangeSorting.call(this);
            } else {
                this.render();
            }
        }
    });

    return BackendSortingDropdown;
});
