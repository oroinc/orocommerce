import BaseView from 'oroui/js/app/views/base/view';
import SettingsModel from './settings-model';
import settingBtnLabelTemplate from 'tpl-loader!orocms/templates/plugins/settings/settings-btn-label-template.html';
import layout from 'oroui/js/layout';

const SettingsView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['appendToPanelId', 'editor', 'settingsPanel']),

    autoRender: true,

    className: 'settings-panel',

    editor: null,

    settingBtnLabelTemplate,

    constructor: function SettingsView(...args) {
        return SettingsView.__super__.constructor.apply(this, args);
    },

    initialize({props = {}, ...options}) {
        this.model = new SettingsModel(props);
        SettingsView.__super__.initialize.call(this, options);
    },

    render() {
        SettingsView.__super__.render.call(this);

        this.createPanel();
    },

    createPanel() {
        const {Panels} = this.editor;

        const panels = Panels.getPanel(this.appendToPanelId) || Panels.addPanel({
            id: this.appendToPanelId
        });

        this.$el.append(this.settingsPanel.view.$el);

        panels.set('appendContent', this.el).trigger('change:appendContent');
        // Until can't change button view from GrapesJS Panel API, need change markup manually
        this.$('.gjs-pn-btn').each((index, btn) => {
            if (!btn.id) {
                return;
            }

            const button = Panels.getButton('settings', btn.id);
            const {label = '', info = ''} = button.get('data') || {};
            const labelEl = document.createElement('span');
            labelEl.innerHTML = settingBtnLabelTemplate({
                label,
                info
            });

            btn.parentNode.insertBefore(labelEl, btn);
        });

        layout.initPopover(this.$el);
    },

    toggle(state = true) {
        this.$el.toggleClass('show', state);
    }
});

export default SettingsView;
