import {pick} from 'underscore';
import GrapesJS from 'grapesjs';
import * as types from '../../types';
import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';

const DEFAULTS = {
    exclude: [],
    include: []
};

const ComponentTypesPlugin = (editor, options = DEFAULTS) => {
    Object.values(types).forEach(Constructor => {
        const options = Constructor.options ?? {};
        ComponentManager.registerComponentType(Constructor.type, {
            Constructor,
            ...options
        });
    });

    editor.componentManager = new ComponentManager({
        editor,
        typeBuildersOptions: pick(options, 'excludeContentBlockAlias', 'excludeContentWidgetAlias')
    });
};

ComponentTypesPlugin.priority = 1000;

export default GrapesJS.plugins.add('component-types-plugin', ComponentTypesPlugin);
