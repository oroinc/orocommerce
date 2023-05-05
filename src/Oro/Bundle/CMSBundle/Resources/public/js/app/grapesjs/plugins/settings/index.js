import GrapesJS from 'grapesjs';
import SettingsView from './settings-view';

export default GrapesJS.plugins.add('wysiwyg-settings', editor => {
    const {Panels, Commands} = editor;

    const settingsPanel = Panels.addPanel({
        id: 'settings',
        visible: true
    });

    Commands.add('toggle-settings-panel', {
        settingsPanelView: null,

        toggle(show = true) {
            this.settingsPanelView && this.settingsPanelView.toggle(show);
        },

        run(editor) {
            if (!this.settingsPanelView) {
                this.settingsPanelView = new SettingsView({
                    editor,
                    settingsPanel,
                    appendToPanelId: 'views-container'
                });
            }

            this.toggle();
        },

        stop(editor) {
            this.toggle(false);
        }
    });

    Panels.addButton('views', {
        id: 'settings',
        order: 50,
        command: 'toggle-settings-panel',
        className: 'fa fa-cog',
        attributes: {
            title: 'Settings'
        }
    });

    Panels.removeButton('options', 'sw-visibility');
    Panels.addButton('settings', {
        active: true,
        id: 'sw-visibility',
        className: 'gjs-pn-btn--switch',
        command: 'sw-visibility',
        context: 'sw-visibility',
        data: {
            label: 'Show components outline',
            info: 'Show/Hide outline around components on editor canvas'
        },
        attributes: {
            id: 'sw-visibility',
            title: 'View components'
        }
    });
});
