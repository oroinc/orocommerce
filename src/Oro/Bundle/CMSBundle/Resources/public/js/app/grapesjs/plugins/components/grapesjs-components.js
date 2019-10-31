define(function(require) {
    'use strict';

    const GrapesJS = require('grapesjs');
    const _ = require('underscore');
    const ComponentRestriction = require('orocms/js/app/grapesjs/plugins/components/component-restriction');
    const ComponentsModule = require('orocms/js/app/grapesjs/components/component-manager');

    return GrapesJS.plugins.add('grapesjs-components', function(editor, options) {
        editor.ComponentRestriction = new ComponentRestriction(editor, options);
        editor.ComponentModule = new ComponentsModule(
            _.extend({
                builder: editor
            }, _.pick(options, 'excludeContentBlockAlias', 'excludeContentWidgetAlias'))
        );

        editor.Panels.removeButton('options', 'preview');
    });
});
