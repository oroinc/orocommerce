import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const MapTypeBuilder = BaseTypeBuilder.extend({
    constructor: function MapTypeBuilder(options) {
        MapTypeBuilder.__super__.constructor.call(this, options);
    },

    // Empty execute to prevent call parent method from BaseTypeBuilder
    execute: () => {}
}, {
    isAllowed(options) {
        const {componentType, editor} = options;
        const mapModel = editor.DomComponents.getType(componentType).model;

        return editor.ComponentRestriction.isAllowedDomain(mapModel.prototype.defaults.mapUrl);
    }
});

export default MapTypeBuilder;
