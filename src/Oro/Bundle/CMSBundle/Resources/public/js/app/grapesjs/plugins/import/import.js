define(function(require) {
    'use strict';

    const GrapesJS = require('grapesjs');
    const ImportDialofView = require('orocms/js/app/grapesjs/plugins/import/import-dialog-view');
    const _ = require('underscore');

    return GrapesJS.plugins.add('grapesjs-import', function(editor, options) {
        const Commands = editor.Commands;

        Commands.add('gjs-open-import-webpage', {
            run: function(editor) {
                new ImportDialofView(_.extend({}, options, {
                    editor: editor,
                    commandId: this.id
                }));
            }
        });
    });
});
