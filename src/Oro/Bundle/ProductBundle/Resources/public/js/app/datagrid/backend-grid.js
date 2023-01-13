define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const pageStateChecker = require('oronavigation/js/app/services/page-state-checker');
    const Grid = require('orodatagrid/js/datagrid/grid');
    const BackendToolbar = require('oroproduct/js/app/datagrid/backend-toolbar');
    const BackendSelectAllHeaderCell = require('oroproduct/js/app/datagrid/header-cell/backend-select-all-header-cell');
    const BackendActionHeaderCell = require('oroproduct/js/app/datagrid/header-cell/backend-action-header-cell');
    const SelectState = require('oroproduct/js/app/datagrid/products-select-state-model');

    let config = require('module-config').default(module.id);
    config = _.extend({
        massActionsContainer: '[data-mass-actions-container]',
        massActionsStickyContainer: '[data-mass-actions-sticky-container]'
    }, config);

    const BackendGrid = Grid.extend({
        /** @property */
        toolbar: BackendToolbar,

        /** @property */
        themeOptions: {
            optionPrefix: 'backendgrid'
        },

        /** @property */
        massActionsContainer: config.massActionsContainer,

        /** @property */
        massActionsStickyContainer: config.massActionsStickyContainer,

        /** @property */
        visibleState: {
            visible: null
        },

        /**
         * @inheritdoc
         */
        constructor: function BackendGrid(options) {
            BackendGrid.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.header = null;
            this.footer = null;
            this.body = null;
            this.multiSelectRowEnabled = options.multiSelectRowEnabled;
            this.optimizedScreenSize = options.metadata.options.optimizedScreenSize || 'tablet';

            mediator.on('grid-content-loaded', function(params) {
                this.updateGridContent(params);
            }, this);

            BackendGrid.__super__.initialize.call(this, options);

            mediator.on('widget:notFound', function() {
                $(window).off('beforeunload');
            });

            this.hasSelections = this.hasSelections.bind(this);
            pageStateChecker.registerChecker(this.hasSelections);

            this._listenToDocumentEvents();
        },

        _listenToDocumentEvents: function() {
            $(window).on('beforeunload.' + this.cid, this.onWindowUnload.bind(this));
        },

        /**
         * Update grid content after load new content
         *
         * @param {object} params
         */
        updateGridContent: function(params) {
            this.$el.find('.grid-body').html(params.gridContent.html());

            mediator.trigger('datagrid_filters:update', this);
            this.collection.updateState(params.responseJSON.data.options);
            this.collection.reset(params.responseJSON.data.data);

            this._afterRequest(params);
        },

        /**
         * @inheritdoc
         */
        backgridInitialize: function() {
            if (this.selectState === null) {
                this.selectState = new SelectState();
            }

            this.listenTo(this.collection, {
                'remove': this.onCollectionModelRemove,
                'updateState': this.onCollectionUpdateState,
                'backgrid:selected': this.onSelectRow,
                'backgrid:selectAll': this.selectAll,
                'backgrid:selectAllVisible': this.selectAllVisible,
                'backgrid:selectNone': this.selectNone,
                'backgrid:isSelected': this.isSelected,
                'backgrid:getSelected': this.getSelected,
                'backgrid:setVisibleState': this.setVisibleState,
                'backgrid:getVisibleState': this.getVisibleState,
                'backgrid:checkUnSavedData': this.checkUnSavedData,
                'backgrid:hasMassActions': this.hasMassActions
            });
        },

        /**
         * @inheritdoc
         */
        render: function() {
            this.$grid = this.$(this.selectors.grid);

            this.renderToolbar();
            this.renderNoDataBlock();
            this.renderLoadingMask();
            if (this.multiSelectRowEnabled) {
                this.renderActionsArea();
            }

            this.listenTo(this.collection, 'reset', this.renderNoDataBlock);

            this._deferredRender();

            mediator.trigger('grid_load:complete', this.collection, this.$grid);

            this.initLayout({
                collection: this.collection
            }).always(() => {
                this.rendered = true;
                /**
                 * Backbone event. Fired when the grid has been successfully rendered.
                 * @event rendered
                 */
                this.trigger('rendered');

                /**
                 * Backbone event. Fired when data for grid has been successfully rendered.
                 * @event grid_render:complete
                 */
                mediator.trigger('grid_render:complete', this.$el);
                this._resolveDeferredRender();
            });

            this.rendered = true;
            const self = this;

            this.switchAppearanceClass(_.result(this.metadata.state, 'appearanceType'));

            this.collection.on('gridContentUpdate', function() {
                self._beforeRequest();
            });
            return this;
        },

        /**
         * @inheritdoc
         */
        _afterRequest: function(jqXHR) {
            if (this.requestsCount === 1) {
                this.initLayout({collection: this.collection});
            }
            BackendGrid.__super__._afterRequest.call(this, jqXHR);
        },

        /**
         * @inheritdoc
         */
        undelegateEvents: function() {
            this.collection.off('gridContentUpdate');
            mediator.off('grid-content-loaded');

            BackendGrid.__super__.undelegateEvents.call(this);
        },

        renderActionsArea: function() {
            // Don't render Actions without data
            if (!this.massActions.length) {
                return;
            }

            this.selectAllHeaderCell = new BackendSelectAllHeaderCell({
                collection: this.collection,
                selectState: this.selectState,
                optimizedScreenSize: this.optimizedScreenSize
            });

            $(this.massActionsContainer).append(this.selectAllHeaderCell.$el);

            this.selectHeaderActionCell = new BackendActionHeaderCell({
                collection: this.collection,
                column: this.columns.findWhere('massActions'),
                selectState: this.selectState,
                optimizedScreenSize: this.optimizedScreenSize
            });

            $(this.massActionsStickyContainer).append(this.selectHeaderActionCell.$el);
        },

        setVisibleState: function(state) {
            this.visibleState.visible = state;
            this.$el.toggleClass('row-selection-enabled', state);
        },

        /**
         * @param {Object} obj
         */
        getVisibleState: function(obj) {
            if ($.isEmptyObject(obj) && _.isBoolean(this.visibleState.visible)) {
                obj.visible = this.visibleState.visible;
            }
        },

        /**
         * @param {Object} obj
         */
        hasMassActions: function(obj) {
            if ($.isEmptyObject(obj)) {
                obj.hasMassActions = !!this.massActions.length;
            }
        },

        getMassActions: function() {
            return this.metadataModel.get('massActions');
        },

        setMassActions: function(massActions) {
            this.metadataModel.set('massActions', massActions);
        },

        onWindowUnload: function() {
            if (this.hasSelections()) {
                return __('oro.ui.leave_page_with_unsaved_data_confirm');
            }
        },

        hasSelections: function() {
            return !this.selectState.isEmpty();
        },

        checkUnSavedData: function(obj) {
            let live = true;
            const self = this;

            if (!this.selectState.isEmpty()) {
                const confirm = function() {
                    const answer = window.confirm(__('oro.ui.leave_page_with_unsaved_data_confirm'));
                    if (answer) {
                        // Clear Selected State
                        self.collection.trigger('backgrid:selectNone');
                    }
                    return answer;
                };

                live = confirm();
            }

            if ($.isEmptyObject(obj)) {
                obj.live = live;
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            pageStateChecker.removeChecker(this.hasSelections);
            BackendGrid.__super__.dispose.call(this);
            $(window).off('.' + this.cid);
        },

        /**
         * @inheritDoc
         */
        setGridAriaAttrs() {
            // Nothing to reuse
        }
    });

    return BackendGrid;
});
