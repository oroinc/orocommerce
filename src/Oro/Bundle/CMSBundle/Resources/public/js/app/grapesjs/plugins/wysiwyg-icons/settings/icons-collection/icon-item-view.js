import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orocms/templates/controls/icon-settings/icon-item-preview.html';

const IconItemView = BaseView.extend({
    template,

    className: 'icons-collection-item',

    attributes: {
        tabindex: 0
    },

    events: {
        click: 'onClick',
        keyup: 'onKeyup'
    },

    listen: {
        'change:selected model': 'onSelected'
    },

    constructor: function IconItemView(...args) {
        IconItemView.__super__.constructor.apply(this, args);
    },

    render() {
        IconItemView.__super__.render.call(this);
        this.$el.toggleClass('selected', this.model.get('selected'));
    },

    onClick() {
        this.toggle();
    },

    onKeyup(event) {
        if (event.keyCode === 13) {
            this.toggle();
        }
    },

    onSelected(model, value) {
        this.$el.toggleClass('selected', value);
    },

    toggle() {
        if (!this.model.get('selected')) {
            this.model.set('selected', !this.model.get('selected'));
        }
    }
});

export default IconItemView;
