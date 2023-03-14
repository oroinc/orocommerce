import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const TilesItemType = BaseType.extend({
    constructor: function TilesItemTypeBuilder(...args) {
        TilesItemTypeBuilder.__super__.constructor.apply(this, args);
    },

    modelProps: {
        defaults: {
            name: __('oro.cms.wysiwyg.component.tiles_item.label'),
            classes: ['tiles-item'],
            privateClasses: ['tiles-item'],
            unstylable: [
                'float', 'display', 'label-parent-flex', 'flex-direction',
                'justify-content', 'align-items', 'flex', 'align-self', 'order'
            ]
        }
    },

    viewProps: {
        onRender() {
            this.$el.css('min-height', 50);
        }
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.classList.contains('tiles-item');
    }
}, {
    type: 'tiles-item'
});

export default TilesItemType;
