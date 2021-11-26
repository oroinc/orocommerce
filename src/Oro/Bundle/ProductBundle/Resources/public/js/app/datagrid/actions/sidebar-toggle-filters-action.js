import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import filterSettings from 'oro/filter-settings';
import FullscreenFiltersAction from 'orofrontend/js/app/datafilter/actions/fullscreen-filters-action';
import FiltersManager from 'orofilter/js/filters-manager';

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
            'viewport:change': this.toggleLauncher,
            'grid_load:complete': collection => {
                if (options.datagrid.name === collection.inputName) {
                    this.toggleLauncher();
                }
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

    toggleLauncher() {
        if (filterSettings.isFullScreen() && !this.launcherInstance.$el.is(':visible')) {
            this.addLauncher();
        } else if (!filterSettings.isFullScreen() && this.$launcherPlaceholder === void 0) {
            this.removeLauncher();
        }
    },

    /**
     * There is filters might be hidden by CSS, that's was the reason to method is overridden
     *
     * @param {Object} mode
     */
    toggleFilters: function(mode) {
        if (mode === FiltersManager.STATE_VIEW_MODE) {
            this.datagrid.filterManager.hide();
        } else if (mode === FiltersManager.MANAGE_VIEW_MODE && this.datagrid.filterManager.hasFilters()
        ) {
            this.datagrid.filterManager.show();
        }
    },

    removeLauncher() {
        const $placeholder = $('<span class="hidden"></span>');

        if (this.$launcherPlaceholder) {
            this.$launcherPlaceholder.remove();
            delete this.$launcherPlaceholder;
        }

        this.$launcherPlaceholder = $placeholder;
        this.launcherInstance.$el.replaceWith($placeholder);
    },

    addLauncher() {
        if (!this.$launcherPlaceholder) {
            return;
        }

        this.$launcherPlaceholder.replaceWith(
            this.launcherInstance.render().$el
        );
        delete this.$launcherPlaceholder;
    }
});

export default SidebarToggleFiltersAction;
