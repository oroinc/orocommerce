import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orocms/templates/controls/picture-settings/breakpoints-selector-view.html';
import BreakpointsSelectorModel from './breakpoints-selector-model';

const BreakpointsSelectorView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['editor', 'itemViewInstance']),

    editor: null,

    itemViewInstance: null,

    template,

    events: {
        'change [name="media"]': 'onChangeMedia',
        'click .dropdown-item': 'onChangeBreakpoint'
    },

    listen: {
        'change:normalizeValue model': 'render'
    },

    constructor: function BreakpointsSelectorView(...args) {
        BreakpointsSelectorView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.model = new BreakpointsSelectorModel({
            ...options.modelAttrs
        });

        BreakpointsSelectorView.__super__.initialize.call(this, options);
    },

    onChangeBreakpoint(event) {
        this.model.set('activeBreakpoint', this.model.get('breakpoints')
            .find(
                ({id}) => parseInt(event.currentTarget.dataset.value) === id
            )
        );
        this.$('[name="media"]').trigger('change');
    },

    onChangeMedia(event) {
        this.model.set('normalizeValue', event.target.value, {
            silent: true
        });

        this.trigger('update', {
            normalizeValue: this.model.get('normalizeValue')
        });
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        BreakpointsSelectorView.__super__.dispose.call(this);
    }
});

export default BreakpointsSelectorView;
