import BaseView from 'oroui/js/app/views/base/view';

const PreviewFullNoteView = BaseView.extend({
    /**
     * @inheritDoc
     */
    events: {
        mouseenter: 'onMouseOver'
    },

    autoRender: true,

    popoverConfig: {
        placement: 'bottom',
        animation: false
    },

    /**
     * @inheritDoc
     */
    constructor: function PreviewFullNoteView(options) {
        PreviewFullNoteView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        this.popoverConfig = {...this.popoverConfig, ...options.popoverConfig};
        PreviewFullNoteView.__super__.initialize.call(this, options);
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }
        this.$el.popover('dispose');
        PreviewFullNoteView.__super__.dispose.call(this);
    },

    onMouseOver() {
        const note = this.$('[data-role="note"]')[0];

        if (note.offsetWidth === note.scrollWidth) {
            return;
        }

        this.$el.popover({
            ...this.popoverConfig,
            content: note.innerText
        });
        this.$el.popover('show');
        this.$el.one('mouseleave', () => this.$el.popover('dispose'));
    }
});

export default PreviewFullNoteView;
