import GrapesJS from 'grapesjs';
import ImportDialogView from 'orocms/js/app/grapesjs/plugins/import/import-dialog-view';

export default GrapesJS.plugins.add('grapesjs-import', function(editor, options = {}) {
    const Commands = editor.Commands;
    const commandId = 'gjs-open-import-webpage';

    editor.importDialogView = new ImportDialogView({
        autoRender: false,
        ...options,
        editor,
        commandId
    });

    Commands.add(commandId, {
        run(editor, sender, props = {}) {
            sender.importDialogView.render(props);
        },
        stop(editor, sender) {
            sender.importDialogView.closeDialog();
        }
    });
});
