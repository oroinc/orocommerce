define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const __ = require('orotranslation/js/translator');
    const template = require('tpl-loader!oroproduct/templates/datagrid/backend-action-header-cell.html');
    const viewportManager = require('oroui/js/viewport-manager').default;
    const ActionHeaderCell = require('orodatagrid/js/datagrid/header-cell/action-header-cell');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    const ActionsPanel = require('oroproduct/js/app/datagrid/backend-actions-panel');
    const FullscreenPopupView = require('orofrontend/default/js/app/views/fullscreen-popup-view');
    const oroui = _.macros('oroui');
    const config = require('module-config').default(module.id);

    const shoppingListAddAction = config.shoppingListAddAction || {
        type: 'addproducts',
        data_identifier: 'product.id',
        frontend_type: 'add-products-mass',
        handler: 'oro_shopping_list.mass_action.add_products_handler',
        is_current: false,
        label: 'oro.shoppinglist.actions.add_to_shopping_list',
        name: 'oro_shoppinglist_frontend_addlineitemlist',
        route: 'oro_shopping_list_add_products_massaction',
        route_parameters: {},
        frontend_handle: 'ajax',
        confirmation: false
    };
    const modes = {
        GROUP: 'Group',
        GROUPDROPDOWN: 'GroupDropdown',
        FULLSCREEN: 'Fullscreen'
    };

    const BackendActionHeaderCell = ActionHeaderCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
        className: 'product-action',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        /**
         * @inheritdoc
         */
        actionsPanel: ActionsPanel,

        events: {
            'click [data-fullscreen-trigger]': 'showFullScreen',
            'click [data-undo-selection]': 'undoSelection'
        },

        /**
         * Possible way to render actions
         * {string}
         */
        renderMode: modes.GROUPDROPDOWN,

        /**
         * @inheritdoc
         */
        constructor: function BackendSelectHeaderCell(options) {
            BackendSelectHeaderCell.__super__.constructor.call(this, options);
        },


        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!options.optimizedScreenSize) {
                throw new Error('The "optimizedScreenSize" option is required.');
            }

            this.optimizedScreenSize = options.optimizedScreenSize;
            this.selectState = options.selectState;

            BackendActionHeaderCell.__super__.initialize.call(this, options);

            ShoppingListCollectionService.shoppingListCollection.done(collection => {
                this.listenTo(collection, 'change', this._onShoppingListsRefresh.bind(this));
            });

            this.defineRenderingStrategy();
        },

        /**
         * @inheritdoc
         */
        delegateListeners: function() {
            this.listenTo(this.selectState, 'change', _.debounce(this._doActivate.bind(this), 50));
            this.listenTo(mediator, {
                'sticky-panel:toggle-state': this.onStickyPanelToggle.bind(this),
                [`viewport:${this.optimizedScreenSize}`]: this.defineRenderingStrategy.bind(this)
            });
            this.listenTo(this, 'render-mode:changed', state => this.onRenderModeIsChanged());

            return BackendActionHeaderCell.__super__.delegateListeners.call(this);
        },

        _isOptimizedScreen() {
            return viewportManager.isApplicable(this.optimizedScreenSize);
        },

        _showMassActionsInFullscreen() {
            return this.subviewsByName['fullscreen'] && !this.subviewsByName['fullscreen'].disposed;
        },

        defineRenderingStrategy() {
            const prevRenderMode = this.renderMode;

            if (this._isOptimizedScreen()) {
                if (this._showMassActionsInFullscreen()) {
                    this.renderMode = modes.FULLSCREEN;
                } else {
                    this.renderMode = modes.GROUP;
                }
            } else {
                this.renderMode = modes.GROUPDROPDOWN;
            }

            if (prevRenderMode !== this.renderMode) {
                this.trigger('render-mode:changed', {
                    prevRenderMode,
                    renderMode: this.renderMode
                });
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            return BackendActionHeaderCell.__super__.dispose.call(this);
        },

        _doActivate: function(selectState) {
            try {
                this[`_doActivate${this.renderMode}`](selectState);
                this._renderSelectedItemsView(selectState);
                mediator.trigger('layout:reposition');
            } catch (e) {
                throw e;
            }
        },

        _onShoppingListsRefresh: function() {
            const datagrid = this.column.get('datagrid');
            datagrid.resetSelectionState();

            $.ajax({
                method: 'GET',
                url: routing.generate('oro_shopping_list_frontend_get_mass_actions'),
                success: function(availableMassActions) {
                    const newMassActions = {};

                    _.each(availableMassActions, function(massAction, title) {
                        newMassActions[title] = $.extend(true, {}, shoppingListAddAction, massAction, {
                            name: title
                        });
                    });

                    datagrid.setMassActions(newMassActions);
                }
            });

            this.render();
        },

        getActionContainer() {
            return this.$('.datagrid-massaction-actions');
        },

        render: function() {
            this.$el.empty();
            this.$el.append(this.getTemplateFunction()(this.getTemplateData()));
            this.renderActionsPanel();
            this._doActivate(this.selectState);
            return this;
        },

        renderActionsPanel: function() {
            const panel = this.subview('actionsPanel');

            if (!panel.haveActions()) {
                return;
            }

            panel.$el.removeClass(this._replaceablePanelClasses);
            switch (this.renderMode) {
                case modes.GROUP:
                    return this._renderAsGroup();
                case modes.GROUPDROPDOWN:
                    return this._renderAsGroupDropdown();
                case modes.FULLSCREEN:
                    return this._renderAsFullscreen();
                default:
                    break;
            }
        },

        _attributes() {
            return {
                'aria-colindex': null,
                'aria-label': null
            };
        },

        _renderSelectedItemsView(selectState) {
            this.$('.product-selected-counter').text(
                `${__('oro.product.frontend.actions_panel.selected_view', {count: selectState.get('rows').length})}`
            );
        },

        _renderAsGroupDropdown() {
            const panel = this.subview('actionsPanel');
            const togglerId = _.uniqueId('dropdown-');
            const extraClasses = 'btn-group--full dropup';
            const $mainLuncher = panel.getMainLauncher().render().$el.clone(true, true);

            if (panel.actions.length > 1) {
                this.getActionContainer().append(
                    panel.render().$el
                );

                panel.$el.addClass(extraClasses);

                panel.launchers.forEach(launcher => {
                    launcher.$el.addClass('dropdown-item');
                });

                const $dropdownToggle = $('<button></button>', {
                    'id': togglerId,
                    'type': 'button',
                    'class': 'btn btn--inverse dropdown-toggle',
                    'aria-label': __('oro.product.frontend.choose_action'),
                    'data-toggle': 'dropdown',
                    'data-placement': 'top-end'
                });
                $dropdownToggle.html(oroui.renderIcon({name: 'chevron-up'}));

                panel.$el.children().wrapAll($('<div></div>', {
                    'class': 'dropdown-menu',
                    'aria-labelledby': togglerId
                }));

                $dropdownToggle.prependTo(panel.$el);

                $mainLuncher
                    .addClass('btn btn--inverse btn-main add-to-shopping-list-button')
                    .removeClass('disabled')
                    .prependTo(panel.$el);
            } else {
                this.getActionContainer().append(
                    panel.renderMainLauncher().$el
                );
                panel.launchers.forEach(launcher => {
                    launcher.$el.addClass('btn btn--inverse btn--full');
                });

                panel.$el.addClass(extraClasses);
            }

            this._replaceablePanelClasses = `${extraClasses} show`;

            return panel;
        },

        _doActivateGroupDropdown(selectState) {
            if (selectState.isEmpty()) {
                $('[data-action-panel]').addClass('hidden');
                this.subview('actionsPanel').disable();
            } else {
                $('[data-action-panel]').removeClass('hidden');
                this.subview('actionsPanel').enable();
            }
        },

        _renderAsGroup() {
            const panel = this.subview('actionsPanel');
            const extraClasses = 'btn-group--full action-group dropup';

            this.getActionContainer().append(
                panel.renderMainLauncher().$el
            );

            panel.launchers.forEach(launcher => {
                launcher.$el.addClass('btn btn--inverse btn--full');
            });
            panel.$el.addClass(extraClasses);
            if (panel.actions.length > 1) {
                const $dropdownToggle = $('<button></button>', {
                    'type': 'button',
                    'class': 'btn btn--inverse dropdown-toggle',
                    'data-fullscreen-trigger': '',
                    'aria-label': __('oro.product.frontend.choose_action')
                });
                panel.$el.append($dropdownToggle);
                $dropdownToggle.html(oroui.renderIcon({name: 'chevron-up'}));

                panel.launchers.forEach(launcher => {
                    launcher.$el.addClass('btn-main add-to-shopping-list-button');
                });
            }

            this._replaceablePanelClasses = `${extraClasses} show`;

            return panel;
        },

        _doActivateGroup(selectState) {
            if (selectState.isEmpty()) {
                $('[data-action-panel]').addClass('hidden');
                this.subview('actionsPanel').disable();
            } else {
                $('[data-action-panel]').removeClass('hidden');
                this.subview('actionsPanel').enable();
            }
        },

        _renderAsFullscreen() {
            const panel = this.subview('actionsPanel');

            this._replaceablePanelClasses = 'dropdown-menu fullscreen';
            panel.render();
            panel.$el.addClass(this._replaceablePanelClasses);
            panel.launchers.forEach(launcher => {
                launcher.$el.addClass('dropdown-item');
            });

            return panel;
        },

        _doActivateFullscreen() {
            // nothing to do
        },

        undoSelection() {
            this.selectState.trigger('undo-selection');
        },

        showFullScreen() {
            const fullscreen = new FullscreenPopupView({
                contentElement: document.createElement('div'),
                popupIcon: 'chevron-left',
                popupLabel: __('oro.product.frontend.choose_action')
            });

            this.subview('fullscreen', fullscreen);
            this.listenToOnce(fullscreen, {
                show: this.onShowFullScreen,
                beforeclose: this.onBeforeCloseFullScreen,
                close: this.onCloseFullScreen
            });
            this.defineRenderingStrategy();
            fullscreen.show();
        },

        onShowFullScreen() {
            const fullscreen = this.subview('fullscreen');

            fullscreen.content.$el.append(this.renderActionsPanel().$el);
            fullscreen.$popup.on(`click${fullscreen.eventNamespace()}`, '.action', e => fullscreen.close());
        },

        onBeforeCloseFullScreen() {
            const fullscreen = this.subview('fullscreen');

            fullscreen.$popup.off(fullscreen.eventNamespace());
        },

        onCloseFullScreen() {
            const fullscreen = this.subview('fullscreen');

            fullscreen.dispose();
            this.defineRenderingStrategy();
        },

        onStickyPanelToggle(state) {
            // Verify that $el is rendered is sticky panel
            if (state.$element.find(this.$el).length) {
                this.defineRenderingStrategy();
            }
        },

        onRenderModeIsChanged() {
            const fullscreen = this.subview('fullscreen');

            // Remove fullscreen popup in large screen
            if (fullscreen && !this._isOptimizedScreen()) {
                this.stopListening(fullscreen);
                this.removeSubview('fullscreen');
            }

            this.render();
        }
    });

    return BackendActionHeaderCell;
});
