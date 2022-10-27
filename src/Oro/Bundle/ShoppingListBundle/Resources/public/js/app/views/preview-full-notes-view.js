import BaseView from 'oroui/js/app/views/base/view';

const PreviewFullNotesView = BaseView.extend({
    /**
     * @inheritdoc
     */
    events: {
        'mouseenter [data-role="notes"]': 'onMouseOver'
    },

    autoRender: true,

    popoverConfig: {
        placement: 'bottom',
        animation: false
    },

    /**
     * @inheritdoc
     */
    constructor: function PreviewFullNotesView(options) {
        const {_initEvent: initEvent, ...restOptions} = options;
        PreviewFullNotesView.__super__.constructor.call(this, restOptions);
        if (initEvent) {
            this.onMouseOver();
        }
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.popoverConfig = {...this.popoverConfig, ...options.popoverConfig};
        PreviewFullNotesView.__super__.initialize.call(this, options);
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }
        this.$el.popover('dispose');
        PreviewFullNotesView.__super__.dispose.call(this);
    },

    onMouseOver() {
        const notes = this.$('[data-role="notes"]')[0];

        if (notes.offsetWidth === notes.scrollWidth) {
            return;
        }

        this.$el.popover({
            ...this.popoverConfig,
            content: notes.innerText
        });
        this.$el.popover('show');
        this.$el.one('mouseleave [data-role="notes"]', () => this.$el.popover('dispose'));
    }
});

export default PreviewFullNotesView;
