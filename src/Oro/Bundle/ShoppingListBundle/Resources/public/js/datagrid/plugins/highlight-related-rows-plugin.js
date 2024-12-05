import $ from 'jquery';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';

const HighlightRelatedRowsPlugin = BasePlugin.extend({
    /**
     * @type {string}
     */
    highlightClass: 'hover',

    constructor: function HighlightRelatedRowsPlugin(grid, options) {
        HighlightRelatedRowsPlugin.__super__.constructor.call(this, grid, options);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.unhighlight();

        HighlightRelatedRowsPlugin.__super__.dispose.call(this);
    },

    enable() {
        if (this.enabled) {
            return;
        }

        this.main.$el.on(`mouseenter${this.eventNamespace()}`, '.grid-row', this.highlight.bind(this));
        this.main.$el.on(`mouseleave${this.eventNamespace()}`, '.grid-row', this.unhighlight.bind(this));
        HighlightRelatedRowsPlugin.__super__.enable.call(this);
    },

    disable() {
        if (!this.enabled) {
            return;
        }
        this.unhighlight();
        this.main.$el.off(this.ownEventNamespace());
        HighlightRelatedRowsPlugin.__super__.disable.call(this);
    },

    highlight(e) {
        this.unhighlight();

        const id = $(e.currentTarget).attr('data-row-id');

        if (typeof id !== 'string') {
            return;
        }
        this.main.$el.find(`[data-related-row="${id}"]`).addClass(this.highlightClass);
    },

    unhighlight(e) {
        this.main.$el.find('.grid-row').removeClass(this.highlightClass);
    }
});

export default HighlightRelatedRowsPlugin;
