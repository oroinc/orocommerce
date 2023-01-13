import GrapesJS from 'grapesjs';
import ImportDialogView from 'orocms/js/app/grapesjs/plugins/import/import-dialog-view';

export default GrapesJS.plugins.add('grapesjs-import', function(editor, options = {}) {
    const {Commands, Panels} = editor;
    const commandId = 'gjs-open-import-webpage';

    Panels.removeButton('options', 'export-template');
    Panels.addButton('options', {
        id: commandId,
        className: 'fa fa-download',
        command(editor) {
            editor.runCommand(commandId);
        }
    });

    editor.importDialogView = new ImportDialogView({
        autoRender: false,
        ...options,
        editor,
        commandId
    });

    Commands.add(commandId, {
        run(editor, sender, props = {}) {
            sender.importDialogView.render(props);

            return sender.importDialogView;
        },
        stop(editor, sender) {
            sender.importDialogView.closeDialog();
        }
    });
});
