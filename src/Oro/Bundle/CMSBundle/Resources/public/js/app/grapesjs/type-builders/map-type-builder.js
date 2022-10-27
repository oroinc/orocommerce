import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const MapTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'map',

    button: {
        defaultStyle: {
            height: '350px',
            width: '100%'
        }
    },

    modelMixin: {
        defaults: {
            tagName: 'iframe'
        }
    },

    constructor: function MapTypeBuilder(options) {
        MapTypeBuilder.__super__.constructor.call(this, options);
    }
}, {
    isAllowed(options) {
        const {componentType, editor} = options;
        const mapModel = editor.Components.getType(componentType).model;

        return editor.ComponentRestriction.isAllowedDomain(mapModel.prototype.defaults.mapUrl);
    }
});

export default MapTypeBuilder;
