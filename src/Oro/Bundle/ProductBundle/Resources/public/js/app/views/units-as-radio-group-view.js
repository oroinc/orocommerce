import template from 'tpl-loader!oroproduct/templates/units-as-radio-group.html';
import BaseView from 'oroui/js/app/views/base/view';
import InputWidgetManager from 'oroui/js/input-widget-manager';

const UnitsAsRadioGroupView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'units', '$select', 'hideSelect', 'title', 'icon'
    ]),

    /**
     * @inheritdoc
     */
    template,

    /**
     * @inheritdoc
     */
    events: {
        'change [type="radio"]': 'onRadioChange'
    },

    /**
     * @inheritdoc
     */
    noWrap: true,

    /**
     * Hides a select element after rendering
     * @property {boolean}
     */
    hideSelect: true,

    /**
     * @property {string|null}
     */
    title: null,

    /**
     * @property {string|null}
     */
    icon: null,

    constructor: function UnitsAsRadioGroupView(options) {
        UnitsAsRadioGroupView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        UnitsAsRadioGroupView.__super__.initialize.call(this, options);

        if (!this.units) {
            throw new Error('The property "units" is required');
        }

        if (!this.$select) {
            throw new Error('The property "$select" is required');
        }
    },

    /**
     * @inheritdoc
     */
    getTemplateData() {
        const data = UnitsAsRadioGroupView.__super__.getTemplateData.call(this);
        data.units = this.units;
        data.title = this.title;
        data.icon = this.icon;

        if (Array.isArray(data.units)) {
            data.units = data.units.reduce((obj, unit) => {
                obj[unit] = unit;
                return obj;
            }, {});
        }
        data.selectedValue = this.$select.val();

        return data;
    },

    /**
     * @inheritdoc
     */
    delegateEvents() {
        UnitsAsRadioGroupView.__super__.delegateEvents.call(this);

        this.$select.on(`change${this.eventNamespace()}`, this.onSelectChange.bind(this));
        this.$select.on(`input-widget:init${this.eventNamespace()}`, this.onWidgetInit.bind(this));

        return this;
    },

    /**
     * @inheritdoc
     */
    undelegateEvents() {
        UnitsAsRadioGroupView.__super__.undelegateEvents.call(this);

        if (this.$select) {
            this.$select.off(this.eventNamespace());
        }

        return this;
    },

    /**
     * Change on radio event handler
     * @param {Object} e
     */
    onRadioChange(e) {
        if (this.$select.val() !== e.currentTarget.value) {
            this.$select.val(e.currentTarget.value).trigger('change');
        }
    },

    /**
     * Change on select event handler
     * @param {Object} e
     */
    onSelectChange(e) {
        if (this.getRadioValue() !== e.currentTarget.value) {
            this.render();
        }
    },

    /**
     * Widget is init on select event handler
     * @param {Object} e
     */
    onWidgetInit() {
        this.toHideSelect();
    },

    /**
     * @inheritdoc
     */
    render() {
        const $oldEl = this.$el;

        this.toHideSelect();
        UnitsAsRadioGroupView.__super__.render.call(this);

        // Re-rendering process
        if (document.contains($oldEl[0])) {
            // replace old element and keep original place in DOM
            $oldEl.replaceWith(this.el);
        }

        return this;
    },

    /**
     * Hides an original select
     */
    toHideSelect() {
        if (this.hideSelect === false) {
            return this;
        }
        if (InputWidgetManager.hasWidget(this.$select)) {
            this.$select.inputWidget('dispose');
        }

        this.$select.addClass('no-input-widget hidden').attr('data-skip-input-widgets', '');

        return this;
    },

    /**
     * Get a value of selected radio button
     * @returns {string}
     */
    getRadioValue() {
        return this.$('[type="radio"]:checked').val();
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        this.undelegateEvents();

        delete this.units;

        this.$select.removeClass('hidden');
        this.$select.removeClass('no-input-widget');
        delete this.$select;


        UnitsAsRadioGroupView.__super__.dispose.call(this);
    }
});

export default UnitsAsRadioGroupView;
