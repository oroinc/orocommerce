import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';

const FilterExtraHintView = BaseView.extend({
    /**
     * @inheritDoc
     */
    attributes: {
        'class': 'filter-extra-hint'
    },

    hintContentClass: 'filter-extra-hint-text',

    hintContainerSelector: '.filter-item-hint',

    filter: null,

    /**
     * @inheritDoc
     */
    tagName: 'span',

    /**
     * @inheritDoc
     */
    optionNames: BaseView.prototype.optionNames.concat(['filter', 'hintContentClass', 'hintContainerSelector']),

    /**
     * @inheritDoc
     */
    constructor: function FilterExtraHintView(options) {
        FilterExtraHintView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        if (!options.filter) {
            throw new Error('Required option filter not found.');
        }

        this.setElement(this.getHintContainer());
        FilterExtraHintView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritDoc
     */
    delegateEvents: function() {
        FilterExtraHintView.__super__.delegateEvents.call(this);

        this.listenTo(this.filter, 'update', this.updateHintContent);
        return this;
    },

    /**
     * @inheritDoc
     */
    render() {
        this.$el.attr(this.attributes);
        this.updateHintContent();
        return this;
    },

    getHintContainer() {
        return this.filter.$el.find(this.hintContainerSelector);
    },

    /**
     * Updates hint element with actual criteria value
     */
    updateHintContent() {
        let {hint} = this.filter.getState();

        if (hint === null) {
            hint = '';
        }

        hint = hint.trim();

        if (hint.length) {
            this.el.setAttribute('title', hint);
        } else {
            this.el.removeAttribute('title');
        }

        this.el.innerHTML = `<span class="${this.hintContentClass}">${_.escape(hint)}</span>`;

        return this;
    },

    /**
     * @inheritDoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.el.removeAttribute('title');

        delete this.filter;
        FilterExtraHintView.__super__.dispose.call(this);
    }
});

export default FilterExtraHintView;

