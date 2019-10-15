define(function(require) {
    'use strict';

    var GrapesJS = require('grapesjs');
    var _ = require('underscore');
    var ComponentRestriction = require('orocms/js/app/grapesjs/plugins/components/component-restriction');
    var ComponentsModule = require('orocms/js/app/grapesjs/components/component-manager');

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
