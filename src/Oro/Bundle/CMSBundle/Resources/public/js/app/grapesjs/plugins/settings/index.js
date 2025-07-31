import __ from 'orotranslation/js/translator';
import GrapesJS from 'grapesjs';
import SettingsView from './settings-view';

export default GrapesJS.plugins.add('wysiwyg-settings', (editor, {editorView} = {}) => {
    const state = editorView.getState();
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
            label: __('oro.cms.wysiwyg.settings.sw_visibility.label'),
            info: __('oro.cms.wysiwyg.settings.sw_visibility.info')
        },
        attributes: {
            id: 'sw-visibility',
            title: 'View components'
        }
    });

    const showOffsetCommand = 'show-offset-command';

    Commands.add(showOffsetCommand, {
        run() {
            state.set('showOffsets', true);
        },
        stop() {
            state.set('showOffsets', false);
        }
    });

    Panels.addButton('settings', {
        active: state.get('showOffsets'),
        id: 'show-offsets-setting',
        className: 'gjs-pn-btn--switch',
        command: showOffsetCommand,
        data: {
            label: __('oro.cms.wysiwyg.settings.show_offsets_setting.label'),
            info: __('oro.cms.wysiwyg.settings.show_offsets_setting.info')
        },
        attributes: {
            id: 'show-offsets-setting'
        }
    });
});
