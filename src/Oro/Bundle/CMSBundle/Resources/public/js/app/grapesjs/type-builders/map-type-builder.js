import _ from 'underscore';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const MapTypeBuilder = BaseTypeBuilder.extend({
    constructor: function MapTypeBuilder(options) {
        MapTypeBuilder.__super__.constructor.call(this, options);
    },

    initialize(options) {
        Object.assign(this, _.pick(options, 'editor', 'componentType'));
    },

    execute() {
        const {BlockManager} = this.editor;
        const component = BlockManager.get(this.componentType);
        const content = component.get('content');

        content.style = {
            height: '350px',
            width: '100%'
        };
    }
}, {
    isAllowed(options) {
        const {componentType, editor} = options;
        const mapModel = editor.DomComponents.getType(componentType).model;

        return editor.ComponentRestriction.isAllowedDomain(mapModel.prototype.defaults.mapUrl);
    }
});

export default MapTypeBuilder;
