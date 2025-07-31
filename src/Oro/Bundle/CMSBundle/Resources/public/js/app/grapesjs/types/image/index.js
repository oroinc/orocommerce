import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import openDigitalAssetsCommand from 'orocms/js/app/grapesjs/modules/open-digital-assets-command';
import TypeView from './image-type-view';
import TypeModel from './image-type-model';

const ImageTypeBuilder = BaseType.extend({
    parentType: 'image',

    TypeModel,

    TypeView,

    commands: {
        'open-digital-assets': openDigitalAssetsCommand
    },

    constructor: function ImageTypeBuilder(options) {
        ImageTypeBuilder.__super__.constructor.call(this, options);
    },

    createPanelButton() {
        this.editor.BlockManager.remove(this.componentType);
    },

    registerEditorCommands() {
        if (this.editor.Commands.has('open-digital-assets')) {
            return;
        }

        ImageTypeBuilder.__super__.registerEditorCommands.call(this);
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.tagName.toLowerCase() === 'img';
    }
}, {
    type: 'image'
});

export default ImageTypeBuilder;
