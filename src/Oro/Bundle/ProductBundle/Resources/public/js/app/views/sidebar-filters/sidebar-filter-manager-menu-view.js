import $ from 'jquery';
import {MultiselectDropdown} from 'oroui/js/app/views/multiselect';
import filterSettings from 'oro/filter-settings';
import template from 'tpl-loader!oroproduct/templates/sidebar-filters/filter-manager-menu.html';

/**
 * SidebarFilterManagerMenuView
 * Extends MultiselectDropdown to manage sidebar filter menu dropdown,
 * supporting dynamic movement of the dropdown based on fullscreen state.
 *
 * @class MultiselectDropdown
 */
const SidebarFilterManagerMenuView = MultiselectDropdown.extend({
    optionNames: MultiselectDropdown.prototype.optionNames.concat(['outerContainer']),

    template,

    /**
     * Selector for the container where filters are rendered outside fullscreen.
     * @property {string}
     */
    outerContainer: '[data-filters-items]',

    /**
     * CSS configuration for the dropdown.
     * @property {Object}
     */
    cssConfig: {
        ...MultiselectDropdown.prototype.cssConfig,
        dropdownMenuHeader: 'sidebar-filter-manager-menu__dropdown-menu-header'
    },

    events: {
        'show.bs.dropdown': 'onShowDropdown'
    },

    listen: {
        'viewport:change mediator': 'updateLayoutForViewport'
    },

    constructor: function SidebarFilterManagerMenuView(...args) {
        SidebarFilterManagerMenuView.__super__.constructor.apply(this, args);
    },

    /**
     * Handles closing the dropdown and removing 'show' class from moved element.
     */
    onClose() {
        this.getToogleButton().dropdown('hide');

        this.movedOutElement && this.movedOutElement.removeClass('show');
    },

    /**
     * Handles showing the dropdown and adding 'show' class to moved element.
     */
    onShowDropdown() {
        this.movedOutElement && this.movedOutElement.addClass('show');

        this.getToogleButton().tooltip('hide');
    },

    /**
     * Renders the view and applies viewport logic.
     * @returns {SidebarFilterManagerMenuView}
     */
    render() {
        SidebarFilterManagerMenuView.__super__.render.call(this);

        this.updateLayoutForViewport();

        return this;
    },

    /**
     * Moves or rolls back the root element based on fullscreen state.
     */
    updateLayoutForViewport() {
        if (filterSettings.isFullScreen() && this.outerContainer) {
            this.rollbackRootElement();
        } else if (!filterSettings.isFullScreen() && this.outerContainer) {
            this.moveRootElementToOuterContainer();
        }
    },

    /**
     * Moves the root element outside the original container.
     */
    moveRootElementToOuterContainer() {
        if (this.movedOutElement) {
            return;
        }

        this.movedOutElement = this.getRootElement();

        $(this.outerContainer).after(this.getRootElement());

        this.delegateEvents();
    },

    /**
     * Rolls back the root element to its original container.
     */
    rollbackRootElement() {
        if (this.movedOutElement) {
            this.movedOutElement.appendTo(this.$('[data-origin-container]'));

            delete this.movedOutElement;
        }
    },

    /**
     * Delegates events, including custom close event for moved element.
     * @param {Object} [events]
     * @returns {SidebarFilterManagerMenuView}
     */
    delegateEvents(events) {
        SidebarFilterManagerMenuView.__super__.delegateEvents.call(this, events);

        if (this.movedOutElement) {
            this.movedOutElement.on(`click${this.eventNamespace()}`, '[data-role="close"]', this.onClose.bind(this));
        }

        return this;
    },

    /**
     * Removes delegated events from moved element.
     * @returns {SidebarFilterManagerMenuView}
     */
    undelegateEvents() {
        SidebarFilterManagerMenuView.__super__.undelegateEvents.call(this);

        if (this.movedOutElement) {
            this.movedOutElement.off(this.eventNamespace());
        }

        return this;
    },

    /**
     * Cleans up and rolls back root element on dispose.
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        this.rollbackRootElement();

        SidebarFilterManagerMenuView.__super__.dispose.call(this);
    }
});

export default SidebarFilterManagerMenuView;
