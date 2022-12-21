import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const MapType = BaseType.extend({
    parentType: 'map',

    button: {
        attributes: {
            'class': 'fa fa-map-o'
        },
        category: 'Basic',
        defaultStyle: {
            height: '350px',
            width: '100%'
        },
        order: 60
    },

    modelProps: {
        defaults: {
            tagName: 'iframe'
        }
    },

    constructor: function MapTypeBuilder(options) {
        MapTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        let result = '';

        if (
            el.nodeType === Node.ELEMENT_NODE &&
            el.tagName.toLowerCase() === 'iframe' &&
            /maps\.google\.com/.test(el.src)
        ) {
            result = {
                type: this.componentType,
                src: el.src
            };
        }

        return result;
    }
}, {
    type: 'map',
    isAllowed(options) {
        const {componentType, editor} = options;
        const mapModel = editor.Components.getType(componentType).model;

        return editor.ComponentRestriction.isAllowedDomain(mapModel.prototype.defaults.mapUrl);
    }
});

export default MapType;
