define(function(require) {
    'use strict';

    const GrapesJS = require('grapesjs');
    const CodeDialogView = require('orocms/js/app/grapesjs/plugins/code/code-dialog-view');
    const _ = require('underscore');

    return GrapesJS.plugins.add('grapesjs-code', function(editor, options) {
        const Commands = editor.Commands;

        Commands.add('gjs-open-code-page', {
            run: function(editor) {
                new CodeDialogView(_.extend({}, options, {
                    editor: editor,
                    commandId: this.id
                }));
            }
        });
    });
});
