import GrapesJS from 'grapesjs';
import ImportDialogView from 'orocms/js/app/grapesjs/plugins/import/import-dialog-view';
import _ from 'underscore';

export default GrapesJS.plugins.add('grapesjs-import', function(editor, options) {
    const Commands = editor.Commands;

    Commands.add('gjs-open-import-webpage', {
        run: function(editor) {
            new ImportDialogView(_.extend({}, options, {
                editor: editor,
                commandId: this.id
            }));
        }
    });
});
