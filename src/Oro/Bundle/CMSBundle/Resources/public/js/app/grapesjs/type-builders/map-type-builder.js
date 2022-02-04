import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const MapTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'map',

    modelMixin: {
        defaults: {
            style: {
                height: '350px',
                width: '100%'
            }
        }
    },

    constructor: function MapTypeBuilder(options) {
        MapTypeBuilder.__super__.constructor.call(this, options);
    }
}, {
    isAllowed(options) {
        const {componentType, editor} = options;
        const mapModel = editor.DomComponents.getType(componentType).model;

        return editor.ComponentRestriction.isAllowedDomain(mapModel.prototype.defaults.mapUrl);
    }
});

export default MapTypeBuilder;
