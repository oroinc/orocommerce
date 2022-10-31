import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import DialogWidget from 'oro/dialog-widget';
import presets from './presets';

import template from 'tpl-loader!orocms/templates/controls/tiles-preset-dialog-view-template.html';

const TilesPresetView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['component']),

    autoRender: true,

    template,

    component: null,

    events: {
        'click .tiles-preset': 'onClick'
    },

    constructor: function TilesPresetView(...args) {
        TilesPresetView.__super__.constructor.apply(this, args);
    },

    getTemplateData() {
        return {
            presets
        };
    },

    render() {
        TilesPresetView.__super__.render.call(this);

        this.subview('dialog', new DialogWidget({
            autoRender: true,
            title: __('oro.cms.wysiwyg.component.tiles.dialog_title'),
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
        this.component.generateTilesByPreset(
            presets.find(({name}) => name === event.currentTarget.dataset.name)
        );

        this.component.em.get('Commands').stop('select-tiles-preset');
    },

    onClose() {
        this.component.em.get('Commands').stop('select-tiles-preset', {
            remove: true
        });
    }
});

export default TilesPresetView;
