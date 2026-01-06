import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import viewportManager from 'oroui/js/viewport-manager';
import template from 'tpl-loader!oroproduct/templates/sidebar-filters/filter-items-hint.html';

const FilterItemsHintView = BaseView.extend({
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

    listen: {
        'viewport:mobile-big mediator': 'updateResetBtnLabel'
    },

    /**
     * @inheritdoc
     */
    attributes: {
        'class': 'filter-box'
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
        mediator.trigger('filters:reset:' + this.gridName, e);
    },

    updateResetBtnLabel() {
        this.$('[data-role="reset-button-label"]').text(viewportManager.isApplicable('mobile-big')
            ? __('oro.filter.reset.short_label')
            : __('oro.filter.reset.label'));
    },

    /**
     * @inheritdoc
     */
    render() {
        FilterItemsHintView.__super__.render.call(this);
        this.$el.addClass(this.renderMode);
        this.$el.attr('data-hint-container', this.gridName);
        this.updateResetBtnLabel();
        return this;
    }
});

export default FilterItemsHintView;
