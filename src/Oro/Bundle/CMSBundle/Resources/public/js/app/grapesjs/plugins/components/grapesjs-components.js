import GrapesJS from 'grapesjs';
import _ from 'underscore';
import ComponentRestriction from 'orocms/js/app/grapesjs/plugins/components/component-restriction';
import ComponentsModule from 'orocms/js/app/grapesjs/components/component-manager';
import ImageExpression from 'orocms/js/app/grapesjs/plugins/components/image-expression';

export default GrapesJS.plugins.add('grapesjs-components', function(editor, options) {
    editor.ComponentRestriction = new ComponentRestriction(editor, options);
    editor.ComponentModule = new ComponentsModule(
        _.extend({
            builder: editor
        }, _.pick(options, 'excludeContentBlockAlias', 'excludeContentWidgetAlias'))
    );

    new ImageExpression(editor);

    editor.Panels.removeButton('options', 'preview');
});
