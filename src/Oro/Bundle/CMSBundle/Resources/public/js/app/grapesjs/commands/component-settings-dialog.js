import __ from 'orotranslation/js/translator';
import SettingsDialogWidget from './views/settings-dialog-widget';

export default {
    run(editor) {
        const {Commands} = editor;
        const component = editor.getSelected();

        this.settingsDialog = new SettingsDialogWidget({
            autoRender: true,
            title: __('oro.cms.wysiwyg.dialog.component_settings.label', {name: component.get('name')}),
            component,
            dialogOptions: {
                modal: true,
                close: () => Commands.stop('component:settings-dialog', {
                    dialogClose: true
                })
            }
        });

        this.settingsDialog.on('saveSettings', data => {
            component.trigger('saveSettings', data);
            Commands.stop('component:settings-dialog');
        });

        return this.settingsDialog;
    },

    stop(editor, sender, {dialogClose} = {}) {
        if (dialogClose) {
            return;
        }

        this.settingsDialog.remove();
    }
};
