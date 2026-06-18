import _ from 'underscore';
import GrapesJS from 'grapesjs';
import * as types from '../../types';
import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import fileTraitInit from 'orocms/js/app/grapesjs/plugins/traits/file-trait';
import hrefTraitInit from 'orocms/js/app/grapesjs/plugins/traits/href-trait';
import radioSelectTraitInit from 'orocms/js/app/grapesjs/plugins/traits/radio-select-trait';
import dividerTraitInit from 'orocms/js/app/grapesjs/plugins/traits/divider-trait';

const DEFAULTS = {
    exclude: [],
    include: []
};

const ComponentTypesPlugin = (editor, options = DEFAULTS) => {
    fileTraitInit({editor});
    hrefTraitInit({editor});
    radioSelectTraitInit({editor});
    dividerTraitInit({editor});

    Object.values(types).forEach(Constructor => {
        const options = Constructor.options ?? {};
        ComponentManager.registerComponentType(Constructor.type, {
            Constructor,
            ...options
        });
    });

    editor.componentManager = new ComponentManager({
        editor,
        typeBuildersOptions: _.pick(options, 'excludeContentBlockAlias', 'excludeContentWidgetAlias')
    });
};

ComponentTypesPlugin.priority = 1000;

export default GrapesJS.plugins.add('component-types-plugin', ComponentTypesPlugin);
