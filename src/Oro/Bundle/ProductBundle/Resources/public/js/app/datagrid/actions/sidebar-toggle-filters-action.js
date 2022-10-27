import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import filterSettings from 'oro/filter-settings';
import FiltersManager from 'orofilter/js/filters-manager';
import FullscreenFiltersAction from 'orofrontend/js/app/datafilter/actions/fullscreen-filters-action';
import SidebarToggleFiltersView from 'oroproduct/js/app/views/sidebar-filters/sidebar-toggle-filters-view';

const SidebarToggleFiltersAction = FullscreenFiltersAction.extend({
    /**
     * @inheritdoc
     */
    constructor: function SidebarToggleFiltersAction(options) {
        SidebarToggleFiltersAction.__super__.constructor.call(this, options);
    },

    initialize(options) {
        SidebarToggleFiltersAction.__super__.initialize.call(this, options);

        this.listenTo(mediator, {
            'viewport:change': this.toggleLaunchers,
            'grid_load:complete': collection => {
                if (options.datagrid.name === collection.inputName) {
                    this.toggleLaunchers();
                }
            }
        });

        this.listenToOnce(this.datagrid, {
            'filterManager:connected': () => {
                const filterManagerIsVisible = this.datagrid.filterManager.isVisible;
                const switchSidebarView = this.subview('switch-sidebar');

                if ((filterManagerIsVisible === false) && switchSidebarView) {
                    switchSidebarView.collapse(0);
                    this.toggleLaunchers();
                }

                this.listenTo(this.filterManager, 'visibility-change', filterManagerIsVisible => {
                    // Synchronize Filter Manager visibility with a sidebar one.
                    // The Filter Manager might be hidden programmatically after resetting its state.
                    if (
                        filterSettings.isFullScreen() === false &&
                        switchSidebarView.$el.is(':visible') &&
                        filterManagerIsVisible !== switchSidebarView.sidebarExpanded
                    ) {
                        this.datagrid.filterManager[switchSidebarView.sidebarExpanded ? 'show' : 'hide']();
                    }
                });
            }
        });
    },

    updateFiltersStateView() {
        if (
            this.filterManager === void 0 ||
            this.fullscreenFilters.isPopupOpen()
        ) {
            return;
        }

        const mode = this.filterManager.getViewMode();

        if (mode === FiltersManager.MANAGE_VIEW_MODE && filterSettings.isFullScreen()) {
            this.filterManager.setViewMode(FiltersManager.STATE_VIEW_MODE);
        } else if (mode === FiltersManager.STATE_VIEW_MODE && !filterSettings.isFullScreen()) {
            this.filterManager.setViewMode(FiltersManager.MANAGE_VIEW_MODE);
        }
    },

    toggleLaunchers() {
        if (filterSettings.isFullScreen() && !this.launcherInstance.$el.is(':visible')) {
            this.useFullScreenLauncher();
        } else if (!filterSettings.isFullScreen() && this.$launcherPlaceholder === void 0) {
            this.useSwitchSidebarLauncher();
        }
    },

    /**
     * There is filters might be hidden by CSS, that was the reason to override that method
     *
     * @param {Object} mode
     */
    toggleFilters: function(mode) {
        if (mode === FiltersManager.STATE_VIEW_MODE) {
            this.datagrid.filterManager.hide();
        } else if (
            mode === FiltersManager.MANAGE_VIEW_MODE && this.datagrid.filterManager.hasFilters()
        ) {
            this.datagrid.filterManager.show();
        }
    },

    useSwitchSidebarLauncher() {
        this.launcherInstance.$el.remove();
        this.subview('switch-sidebar').render();
    },

    useFullScreenLauncher() {
        this.subview('switch-sidebar').$el.remove();
        this.$launcherParent.append(
            this.launcherInstance.render().$el
        );
    },

    createLauncher: function(options) {
        const launcher = SidebarToggleFiltersAction.__super__.createLauncher.call(this, options);
        const triggerSidebar = new SidebarToggleFiltersView({
            $content: $('[data-role="page-content"]'),
            $sidebar: $('[data-role="page-sidebar"]'),
            sidebarExpanded: this.datagrid.themeOptions.sidebarExpanded
        });

        this.listenTo(triggerSidebar, {
            'toggle-sidebar:after-collapse': () => {
                this.filterManager.hide();
                this.filterManager.trigger('filters-render-mode-changed', {
                    renderMode: this.filterManager.renderMode,
                    isAsInitial: false
                });
            },
            'toggle-sidebar:before-expand': () => {
                this.filterManager.show();
                this.filterManager.trigger('filters-render-mode-changed', {
                    renderMode: this.filterManager.renderMode,
                    isAsInitial: true
                });
            }
        });
        this.subview('switch-sidebar', triggerSidebar);
        this.launcherInstance.on('appended', () => {
            this.$launcherParent = this.launcherInstance.$el.parent();
            triggerSidebar.container = this.$launcherParent;
            triggerSidebar.render();
            triggerSidebar.$el.hide();
        });

        return launcher;
    }
});

export default SidebarToggleFiltersAction;
