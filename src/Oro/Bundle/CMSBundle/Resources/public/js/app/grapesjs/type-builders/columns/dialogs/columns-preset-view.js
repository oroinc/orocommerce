import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import DialogWidget from 'oro/dialog-widget';
import presets from './presets';

import template from 'tpl-loader!orocms/templates/controls/columns-preset-dialog-view-template.html';

const ColumnsPresetView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['component']),

    autoRender: true,

    template,

    component: null,

    events: {
        'click .column-preset': 'onClick'
    },

    constructor: function ColumnsPresetView(...args) {
        ColumnsPresetView.__super__.constructor.apply(this, args);
    },

    getTemplateData() {
        return {
            presets
        };
    },

    render() {
        ColumnsPresetView.__super__.render.call(this);

        this.subview('dialog', new DialogWidget({
            autoRender: true,
            title: __('oro.cms.wysiwyg.component.columns.dialog_title'),
            el: this.el,
            dialogOptions: {
                modal: true,
                autoResize: true,
                resizable: true,
                close: this.onClose.bind(this)
            }
        }));
    },

    onClick(event) {
        this.component.generateColumnsByPreset(
            presets.find(({name}) => name === event.currentTarget.dataset.name)
        );

        this.component.em.get('Commands').stop('select-columns-preset');
    },

    onClose() {
        this.component.em.get('Commands').stop('select-columns-preset', {
            remove: true
        });
    }
});

export default ColumnsPresetView;
