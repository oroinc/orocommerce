import _ from 'underscore';
import GrapesJS from 'grapesjs';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import ImageExpression from 'orocms/js/app/grapesjs/plugins/components/image-expression';

export default GrapesJS.plugins.add('grapesjs-components', function(editor, options) {
    editor.ComponentRestriction = new ComponentRestriction(editor, options);

    const componentManager = new ComponentManager({
        editor,
        typeBuildersOptions: _.pick(options, 'excludeContentBlockAlias', 'excludeContentWidgetAlias')
    });
    const imageExpression = new ImageExpression(editor);

    editor.Panels.removeButton('options', 'preview');

    editor.once('destroy', function() {
        imageExpression.destroy();
        componentManager.dispose();
        delete editor.ComponentRestriction;
    });
});
