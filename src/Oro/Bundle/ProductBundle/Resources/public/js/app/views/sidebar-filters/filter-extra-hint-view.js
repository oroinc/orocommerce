import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';

const FilterExtraHintView = BaseView.extend({
    /**
     * @inheritdoc
     */
    attributes: {
        'class': 'filter-extra-hint'
    },

    hintContentClass: 'filter-extra-hint-text',

    hintContainerSelector: '.filter-item-hint',

    filter: null,

    keepElement: true,

    /**
     * @inheritdoc
     */
    tagName: 'span',

    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat(['filter', 'hintContentClass', 'hintContainerSelector']),

    /**
     * @inheritdoc
     */
    constructor: function FilterExtraHintView(options) {
        FilterExtraHintView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        if (!options.filter) {
            throw new Error('Required option filter not found.');
        }

        this.listenTo(this.filter, {
            update: this.updateHintContent,
            rendered: this.render
        });
        this.setElement(this.getHintContainer());
        FilterExtraHintView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     */
    render() {
        this.$el.empty();
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
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.$el
            .removeAttr('title class')
            .addClass(
                this.hintContainerSelector.substring(1)
            );
        delete this.filter;
        FilterExtraHintView.__super__.dispose.call(this);
    }
});

export default FilterExtraHintView;

