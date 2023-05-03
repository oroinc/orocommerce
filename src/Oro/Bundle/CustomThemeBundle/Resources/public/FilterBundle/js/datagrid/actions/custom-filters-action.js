import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import viewportManager from 'oroui/js/viewport-manager';
import FullscreenFiltersAction from 'orofrontend/js/app/datafilter/actions/fullscreen-filters-action';
import FiltersManager from 'orofilter/js/filters-manager';

const isFullscreenMode = () => {
    return viewportManager.isApplicable('mobile-landscape');
};
const isCollapseMode = () => {
    return viewportManager.isApplicable(['strict-tablet', 'strict-tablet-small']);
};
const isDropdownMode = () => {
    return viewportManager.isApplicable(['desktop']);
};

const CustomToggleFiltersAction = FullscreenFiltersAction.extend({
    /**
     * @inheritdoc
     */
    constructor: function CustomToggleFiltersAction(options) {
        CustomToggleFiltersAction.__super__.constructor.call(this, options);
    },

    initialize(options) {
        if (!options.collapseFilters) {
            throw new TypeError('The "collapseFilters" option is required.');
        }

        this.collapseFilters = options.collapseFilters;

        this.listenTo(mediator, {
            'viewport:change': this.toggleLauncher,
            'grid_load:complete': collection => {
                if (options.datagrid.name === collection.inputName) {
                    this.toggleLauncher();
                }
            }
        });

        CustomToggleFiltersAction.__super__.initialize.call(this, options);
    },

    updateFiltersStateView() {
        if (
            this.filterManager === void 0 ||
            this.fullscreenFilters.isPopupOpen()
        ) {
            return;
        }

        if (isFullscreenMode()) {
            this.filterManager.setViewMode(FiltersManager.STATE_VIEW_MODE);
        } else if (isCollapseMode()) {
            this.collapseFilters.transformToCollapse(this.filterManager);
            this.filterManager.setViewMode(FiltersManager.MANAGE_VIEW_MODE);
        } else if (isDropdownMode()) {
            this.filterManager.setViewMode(FiltersManager.MANAGE_VIEW_MODE);
            this.collapseFilters.transformToOriginal(this.filterManager);
        }
    },

    /**
     * @inheritdoc
     */
    toggleFilters: function(mode) {
        if (isFullscreenMode()) {
            FullscreenFiltersAction.__super__.toggleFilters.call(this, FiltersManager.STATE_VIEW_MODE);
        } else {
            FullscreenFiltersAction.__super__.toggleFilters.call(this, mode);
        }
    },

    toggleLauncher() {
        if (isFullscreenMode() && !this.launcherInstance.$el.is(':visible')) {
            this.addLauncher();
        } else if (!isFullscreenMode() && this.$launcherPlaceholder === void 0) {
            this.removeLauncher();
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

export default CustomToggleFiltersAction;
