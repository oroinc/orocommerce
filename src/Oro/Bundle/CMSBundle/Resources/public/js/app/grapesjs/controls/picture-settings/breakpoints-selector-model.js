import BaseModel from 'oroui/js/app/models/base/model';

const BreakpointsSelectorModel = BaseModel.extend({
    defaults: {
        breakpoints: [],
        invalid: false,
        errorMessage: '',
        activeBreakpoint: null,
        normalizeValue: ''
    },

    constructor: function BreakpointsSelectorModel(...args) {
        BreakpointsSelectorModel.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        BreakpointsSelectorModel.__super__.initialize.call(this, options);
        this.on('change:activeBreakpoint', this.normalizeValue.bind(this));
    },

    normalizeValue() {
        const activeBreakpoint = this.get('activeBreakpoint');
        const media = [];

        if (activeBreakpoint) {
            if (activeBreakpoint.max) {
                media.push(`(max-width: ${activeBreakpoint.max}px)`);
            }

            if (activeBreakpoint.min) {
                media.push(`(min-width: ${activeBreakpoint.min}px)`);
            }

            if (activeBreakpoint.landscape) {
                media.push(`(orientation: landscape)`);
            }
        }

        this.set('normalizeValue', media.join(' and '));
    }
});

export default BreakpointsSelectorModel;
