import _ from 'underscore';
import GrapesJS from 'grapesjs';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import traitManagerExtends from 'orocms/js/app/grapesjs/plugins/components/trait-manager-extends';

export default GrapesJS.plugins.add('grapesjs-components', function(editor, options) {
    // Overwrite default addType method
    // Check and update manually componentTypes array
    // Check functionality at next version GrapesJS, if need delete wrap function
    editor.DomComponents.addType = _.wrap(editor.DomComponents.addType, (func, typeName, methods) => {
        const dom = func.call(editor.DomComponents, typeName, methods);

        const index = _.findIndex(dom.componentTypes, type => type.id === typeName);

        if (index !== -1) {
            dom.componentTypes[index] = dom.getType(typeName);
        } else {
            dom.componentTypes.unshift(dom.getType(typeName));
        }

        return dom;
    });

    editor.editor.runDefault = _.wrap(editor.editor.runDefault, (func, opts = {}) => {
        if (!editor.editor.get('Commands')) {
            return;
        }

        func.call(editor.editor, opts);
    });

    traitManagerExtends(editor);

    editor.ComponentRestriction = new ComponentRestriction(editor, options);

    const componentManager = new ComponentManager({
        editor,
        typeBuildersOptions: _.pick(options, 'excludeContentBlockAlias', 'excludeContentWidgetAlias')
    });

    editor.Panels.removeButton('options', 'preview');

    editor.once('destroy', function() {
        componentManager.dispose();
        delete editor.ComponentRestriction;
    });
});
