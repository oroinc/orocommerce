import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';

const ShowFilteredOutItemsView = BaseView.extend({
    /**
     * @inheritDoc
     */
    events: {
        click: 'onClick'
    },

    keepElement: false,

    /**
     * @type {Array}
     */
    elements: null,

    /**
     * @type {string}
     */
    hideClass: 'hide',

    /**
     * @inheritDoc
     */
    constructor: function ShowFilteredOutItemsView(options) {
        ShowFilteredOutItemsView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        if (!options.elements) {
            new Error('Option elements is required');
        }

        Object.assign(this, _.pick(options, 'elements', 'hideClass'));

        ShowFilteredOutItemsView.__super__.initialize.call(this, options);
    },

    onClick() {
        const selector = this.elements.map(el => `.${el}`).join(', ');

        document.querySelectorAll(selector)
            .forEach(el => el.classList.remove(this.hideClass));
        this.$el.hide();
        this.dispose();
    },

    /**
     * @inheritDoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        delete this.elements;

        return ShowFilteredOutItemsView.__super__.dispose.call(this);
    }
});

export default ShowFilteredOutItemsView;
