define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const viewportManager = require('oroui/js/viewport-manager').default;
    const SortingDropdown = require('orodatagrid/js/datagrid/sorting/dropdown');
    const Select2View = require('oroform/js/app/views/select2-view');
    const FullscreenSorting = require('oroproduct/js/app/datagrid/sorting/fullscreen-sorting');

    const BackendSortingDropdown = SortingDropdown.extend({
        optionNames: SortingDropdown.prototype.optionNames.concat([
            'fullscreenMode'
        ]),

        /** @property */
        hasSortingOrderButton: false,

        /** @property */
        inlineSortingLabel: true,

        /** @property */
        className: '',

        /**
         * @inheritdoc
         */
        attributes: {
            'data-grid-sorting': ''
        },

        /** @property */
        dropdownClassName: 'oro-select2__dropdown',

        /** @property */
        themeOptions: {
            optionPrefix: 'backendsortingdropdown',
            el: '[data-grid-sorting]'
        },

        /**
         * Viewports for switch to FullScreen mode
         */
        fullscreenMode: 'tablet',

        /**
         * @inheritdoc
         */
        constructor: function BackendSortingDropdown(options) {
            BackendSortingDropdown.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            BackendSortingDropdown.__super__.initialize.call(this, options);
            mediator.on(`viewport:${this.fullscreenMode}`, this.onViewportChange, this);
        },

        /**
         * @inheritdoc
         */
        onChangeSorting: function() {
            const obj = {};
            this.collection.trigger('backgrid:checkUnSavedData', obj);

            if (obj.live) {
                BackendSortingDropdown.__super__.onChangeSorting.call(this);
            } else {
                this.render();
            }
        },

        onViewportChange: function() {
            this.disposeSubview();
            this.initSubview();
        },

        /**
         * @inheritdoc
         */
        initSubview: function() {
            if (viewportManager.isApplicable(this.fullscreenMode)) {
                this.subview('sortingView', new FullscreenSorting({
                    el: this.$('select')
                }));
            } else {
                this.subview('sortingView', new Select2View({
                    el: this.$('select'),
                    select2Config: this.select2Config
                }));
            }
        },

        disposeSubview: function() {
            if (this.subview('sortingView')) {
                this.removeSubview('sortingView');
            }
        }
    });

    return BackendSortingDropdown;
});
