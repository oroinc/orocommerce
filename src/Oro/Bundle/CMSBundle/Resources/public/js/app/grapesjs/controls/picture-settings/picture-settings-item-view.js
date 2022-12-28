import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orocms/templates/controls/picture-settings/picture-settings-item-view.html';
import templateMain from 'tpl-loader!orocms/templates/controls/picture-settings/picture-settings-item-main-view.html';
import BreakpointsSelectorView from './breakpoints-selector-view';

const PictureSettingsItemView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['editor', 'dialog']),

    dialog: null,

    editor: null,

    tagName: 'tr',

    template,

    templateMain,

    events: {
        'click .removeRow': 'removeItem',
        'click .preview': 'updateSourceImage',
        'click .editRow': 'updateSourceImage'
    },

    listen: {
        'change:attributes model': 'render',
        'change:invalid model': 'render',
        'change:sortable model': 'render'
    },

    constructor: function PictureSettingsItemView(...args) {
        PictureSettingsItemView.__super__.constructor.apply(this, args);
    },

    render() {
        PictureSettingsItemView.__super__.render.call(this);

        if (!this.model.get('main')) {
            this.subview('mediaSelector', new BreakpointsSelectorView({
                modelAttrs: {
                    breakpoints: this.getBreakpointsData(),
                    invalid: this.model.get('invalid'),
                    errorMessage: this.model.get('errorMessage'),
                    normalizeValue: this.model.getAttribute('media')
                },
                el: this.$('.media-field-container')[0],
                autoRender: true,
                editor: this.editor
            }));
            this.listenTo(this.subview('mediaSelector'), 'update', this.onChangeMedia.bind(this));
        } else {
            this.$el.addClass('exclude');
        }
    },

    getTemplateFunction(templateKey) {
        if (this.model.get('main')) {
            templateKey = 'templateMain';
        }
        return PictureSettingsItemView.__super__.getTemplateFunction.call(this, templateKey);
    },

    getBreakpointsData() {
        return this.editor.getBreakpoints([])
            .filter(({name}) => /mobile|tablet|desktop/g.test(name))
            .map((breakpoint, index) => {
                return {
                    id: index,
                    landscape: /-landscape/g.test(breakpoint.name),
                    ...breakpoint
                };
            });
    },

    removeItem() {
        this.model.collection.remove(this.model);
    },

    onChangeMedia({normalizeValue}) {
        this.model.updateAttribute('media', normalizeValue, {
            silent: true
        });

        this.model.collection.trigger('validate');
    },

    updateSourceImage() {
        const {Commands} = this.editor;
        const {model} = this;

        Commands.run(
            'open-digital-assets',
            {
                title: __('oro.cms.wysiwyg.digital_asset.image.title'),
                routeName: 'oro_digital_asset_widget_choose_image',
                loadingElement: this.dialog.loadingElement,
                onSelect(digitalAssetModel) {
                    model.updateImageUrl(digitalAssetModel.get('previewMetadata').url);
                }
            }
        );
    },

    getCollection() {
        return this.model.collection;
    },

    toHTML() {
        const attrs = Object.entries(this.model.get('attributes')).reduce((str, [name, value]) => {
            if (this.model.get('main') && name === 'type') {
                return str;
            }
            str += `${name}="${value}" `;
            return str;
        }, '');

        return this.model.get('main') ? `<img ${attrs}>` : `<source ${attrs}>`;
    }
});

export default PictureSettingsItemView;
