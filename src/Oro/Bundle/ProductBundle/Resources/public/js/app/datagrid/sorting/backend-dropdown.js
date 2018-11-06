define(function(require) {
    'use strict';

    var BackendSortingDropdown;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var viewportManager = require('oroui/js/viewport-manager');
    var SortingDropdown = require('orodatagrid/js/datagrid/sorting/dropdown');
    var Select2View = require('oroform/js/app/views/select2-view');
    var FullscreenSorting = require('oroproduct/js/app/datagrid/sorting/fullscreen-sorting');

    BackendSortingDropdown = SortingDropdown.extend({
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
         * @inheritDoc
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
        fullscreenMode: [
            'mobile-landscape',
            'mobile'
        ],

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            BackendSortingDropdown.__super__.initialize.call(this, options);
            mediator.on('viewport:change', this.onViewportChange, this);
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
        },

        onViewportChange: function(viewport) {
            this.disposeSubview();
            this.initSubview(viewport.type);
        },

        /**
         * @inheritDoc
         */
        initSubview: function(vp) {
            var viewport = vp || viewportManager.getViewport().type;

            if (_.contains(this.fullscreenMode, viewport)) {
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
