import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import mediator from 'oroui/js/mediator';
import 'jquery-ui/tabbable';

const SummaryWrapperView = BaseView.extend({
    options: {
        maskClass: 'loading-blur'
    },

    tabbableButtons: null,

    tabbableElements: null,

    /**
     * @inheritdoc
     */
    constructor: function SummaryWrapperView(options) {
        SummaryWrapperView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.options = Object.assign({}, options || {}, this.options);
        const {showMaskEvents = [], hideMaskEvents = []} = this.options;

        this._maskAdded = false;
        this.showMaskEvents = showMaskEvents;
        this.hideMaskEvents = hideMaskEvents;

        this.subscribe(this.showMaskEvents, this.showMask);
        this.subscribe(this.hideMaskEvents, this.hideMask);

        SummaryWrapperView.__super__.initialize.call(this, options);
    },

    subscribe(events = [], handler) {
        if (events.length && _.isFunction(handler)) {
            for (const event of events) {
                mediator.on(event, handler, this);
            }
        }
    },

    findTabbableElements() {
        const $tabbable = this.$el.find(':tabbable');

        this.tabbableButtons = $tabbable.filter((i, el) => el.value !== void 0);
        this.tabbableElements = $tabbable.filter((i, el) => el.value === void 0);
    },

    showMask() {
        if (this.disposed || this._maskAdded) {
            return;
        }

        this.findTabbableElements();
        this.$el.addClass(this.options.maskClass);

        if (this.tabbableButtons) {
            this.tabbableButtons.attr('disabled', true);
        }

        if (this.tabbableElements) {
            this.tabbableElements.addClass('disabled');
            this.tabbableElements.attr('aria-disabled', true);
        }

        this._maskAdded = true;
        return this;
    },

    hideMask() {
        if (this.disposed || !this._maskAdded) {
            return;
        }

        this.$el.removeClass(this.options.maskClass);
        if (this.tabbableButtons) {
            this.tabbableButtons.removeAttr('disabled');
        }

        if (this.tabbableElements) {
            this.tabbableElements.removeClass('disabled');
            this.tabbableElements.removeAttr('aria-disabled');
        }

        this._maskAdded = false;
        return this;
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        delete this.tabbableButtons;
        delete this.tabbableElements;
        delete this._maskAdded;
        mediator.off(null, null, this);

        SummaryWrapperView.__super__.dispose.call(this);
    }
});

export default SummaryWrapperView;
