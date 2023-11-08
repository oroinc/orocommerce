import GrapesJS from 'grapesjs';
import IconType from './icon-type';
import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import IconsService from './icons-service';

export default GrapesJS.plugins.add('wysiwyg-icons', (editor, {baseSvgSpriteUrl} = {}) => {
    ComponentManager.registerComponentType('icon', {
        Constructor: IconType
    });

    editor.em.set('IconsService', new IconsService({
        baseSvgSpriteUrl
    }));

    Object.defineProperty(editor, 'IconsService', {
        get() {
            return editor.em.get('IconsService');
        }
    });
});
