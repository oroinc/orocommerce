import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import template from 'tpl-loader!oroproduct/templates/sidebar-filters/filter-items-hint.html';
import filterSettings from 'oro/filter-settings';
import viewportManager from 'oroui/js/viewport-manager';

import moduleConfig from 'module-config';

const config = {
    toggleBlock: true,
    ...moduleConfig(module.id)
};

const FilterItemsHintView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'toggleBlock'
    ]),

    toggleBlock: config.toggleBlock,

    /**
     * Specific datagrid name
     * @property {string}
     */
    gridName: '',

    /**
     * Extra class name witch used to add separate styles for different modes
     * @property {string} 'dropdown-mode' | 'toggle-mode'
     */
    renderMode: '',

    /**
     * @inheritdoc
     */
    template: template,

    /**
     * @inheritdoc
     */
    events: {
        'click .reset-filter-button': 'resetAllFilters'
    },

    listen() {
        return {
            [`viewport:${filterSettings.fullScreenViewport} mediator`]: 'doToggleBlock'
        };
    },

    /**
     * @inheritdoc
     */
    attributes: {
        'class': 'filter-box',
        'data-sticky-target': 'top-sticky-panel',
        'data-sticky': JSON.stringify({
            isSticky: true,
            autoWidth: true,
            toggleClass: 'datagrid-toolbar-sticky-container',
            placeholderId: 'sticky_element_toolbar'
        })
    },

    /**
     * @inheritdoc
     */
    constructor: function FilterItemsHintView(options) {
        FilterItemsHintView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        _.extend(this, _.pick(options, ['renderMode', 'gridName']));

        FilterItemsHintView.__super__.initialize.call(this, options);
    },

    /**
     * Click handler
     * @param e
     */
    resetAllFilters(e) {
        mediator.trigger('filters:reset', e);
    },

    /**
     * @inheritdoc
     */
    render() {
        FilterItemsHintView.__super__.render.call(this);
        this.$el.addClass(this.renderMode);
        this.$el.attr('data-hint-container', this.gridName);
        this.doToggleBlock();
        return this;
    },

    doToggleBlock() {
        if (this.toggleBlock === false) {
            return;
        }

        viewportManager.isApplicable(filterSettings.fullScreenViewport) ? this.$el.hide() : this.$el.show();
    }
});

export default FilterItemsHintView;
