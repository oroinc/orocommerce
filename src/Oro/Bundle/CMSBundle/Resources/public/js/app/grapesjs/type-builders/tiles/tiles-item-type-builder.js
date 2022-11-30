import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const TilesItemTypeBuilder = BaseTypeBuilder.extend({
    constructor: function TilesItemTypeBuilder(...args) {
        TilesItemTypeBuilder.__super__.constructor.apply(this, args);
    },

    modelMixin: {
        defaults: {
            name: __('oro.cms.wysiwyg.component.tiles_item.label'),
            classes: ['tiles-item'],
            privateClasses: ['tiles-item']
        }
    },

    viewMixin: {
        onRender() {
            this.$el.css('min-height', 50);
        }
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.classList.contains('tiles-item');
    }
});

export default TilesItemTypeBuilder;
