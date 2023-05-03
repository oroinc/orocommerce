import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import DialogWidget from 'oro/dialog-widget';
import PictureSettings from 'orocms/js/app/grapesjs/controls/picture-settings';
import template from 'tpl-loader!orocms/templates/dialogs/picture-settings-dialog-form.html';

const PictureSettingsDialog = DialogWidget.extend({
    options: _.extend({}, DialogWidget.prototype.options, {
        title: __('oro.cms.wysiwyg.toolbar.pictureSettings'),
        focusOk: false,
        initLayoutOptions: {},
        url: false,
        stateEnabled: false,
        dialogOptions: {
            modal: true
        }
    }),

    constructor: function PictureSettingsDialog(...args) {
        PictureSettingsDialog.__super__.constructor.apply(this, args);
    },

    initialize({props, editor, ...options}) {
        this.subview('pictureSettings', new PictureSettings({
            props,
            editor,
            dialog: this
        }));

        PictureSettingsDialog.__super__.initialize.call(this, options);
    },

    render() {
        this.$el.append(template());
        PictureSettingsDialog.__super__.render.call(this);
    },

    _afterLayoutInit() {
        this.form.append(this.subview('pictureSettings').$el);
        PictureSettingsDialog.__super__._afterLayoutInit.call(this);
    },

    getSources() {
        return this.subview('pictureSettings').getData();
    },

    _onAdoptedFormSubmitClick() {
        if (this.invalid) {
            return;
        }
        this.trigger('saveSources');
    },

    blockSaveButton(status) {
        this.actions.adopted.form_submit.attr('disabled', status);
        this.invalid = status;
    }
});

export default PictureSettingsDialog;
