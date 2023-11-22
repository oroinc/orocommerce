import DialogWidget from 'oro/dialog-widget';
import template from 'tpl-loader!orocms/templates/dialogs/settings-dialog.html';

const SettingsDialogWidget = DialogWidget.extend({
    optionNames: DialogWidget.prototype.optionNames.concat(['component']),

    constructor: function SettingsDialogWidget(...args) {
        SettingsDialogWidget.__super__.constructor.apply(this, args);
    },

    render() {
        this.$el.append(template());

        this.component.settings.forEach(async ({name, getView}) => {
            this.subview(name, await getView({
                container: this.el
            }));

            this.setData(name);
        });

        SettingsDialogWidget.__super__.render.call(this);
    },

    _onAdoptedFormSubmitClick() {
        if (this.invalid) {
            return;
        }

        this.trigger('saveSettings', this.getData());
    },

    _onAdoptedFormResetClick(form) {
        this.trigger('cancel');

        if (form) {
            return SettingsDialogWidget.__super__._onAdoptedFormResetClick.call(this, form);
        }

        this.widget.dialog('close');
    },

    setData(name) {
        this.subview(name).setValue(this.component.get(name));
    },

    getData() {
        return this.component.settings.reduce((data, {name}) => {
            data[name] = this.subview(name).getValue();
            return data;
        }, {});
    }
});

export default SettingsDialogWidget;
