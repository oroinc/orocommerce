import GrapesJS from 'grapesjs';
import CodeDialogView from 'orocms/js/app/grapesjs/plugins/code/code-dialog-view';
import _ from 'underscore';

export default GrapesJS.plugins.add('grapesjs-code', function(editor, options) {
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
