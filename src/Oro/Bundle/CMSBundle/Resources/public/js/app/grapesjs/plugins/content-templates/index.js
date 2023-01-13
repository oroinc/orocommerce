import {escape, sortBy, uniqueId} from 'underscore';
import __ from 'orotranslation/js/translator';
import GrapesJS from 'grapesjs';
import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import CustomCodeTypeBuilder from './content-template-type';
import ContentTemplatesPanelView from './content-templates-panel-view';
import ContentTemplatesPanelModel from './content-templates-panel-model';
import ApiAccessor from 'oroui/js/tools/api-accessor';

const generatePreviewLabel = ({src, sources}, name) => {
    const sourceTags = sources.reduce((str, source) => {
        str += `<source srcset="${escape(source.srcset)}" type="${escape(source.type)}">`;
        return str;
    }, '');

    return `<picture>
                ${sourceTags}
                <img src="${escape(src)}" alt="${name}" loading="lazy">
            </picture>`;
};

const getFlatBlocksData = data => {
    const blocks = data.reduce((items, item) => {
        const name = escape(item.name);

        if (!item.tags.length) {
            item.tags = ['General'];
        }

        item.tags.forEach(tag => {
            items.push({
                id: uniqueId(item.id),
                category: {
                    id: tag.toLowerCase().replace(/\s/g, '_'),
                    label: tag,
                    type: 'content-templates'
                },
                media: generatePreviewLabel(item.previewImage.medium, name),
                label: name,
                content: {
                    type: 'content-template',
                    template: {
                        id: item.id,
                        name
                    }
                },
                activate: true,
                attributes: {
                    'class': 'content-template-block',
                    'title': name
                }
            });
        });

        return items;
    }, []);

    return sortBy(sortBy(blocks, 'label'), ({category}) => category.label);
};

export default GrapesJS.plugins.add('content-templates', (editor, {contentTemplatesData = []} = {}) => {
    const {Commands, Panels} = editor;

    ComponentManager.registerComponentType('content-template', {
        Constructor: CustomCodeTypeBuilder
    });

    Commands.add('toggle-content-templates-panel', {
        contentTemplatesPanelView: null,

        toggle(show = true) {
            this.contentTemplatesPanelView && this.contentTemplatesPanelView.toggle(show);
        },

        run(editor) {
            if (!this.contentTemplatesPanelView) {
                this.contentTemplatesPanelView = new ContentTemplatesPanelView({
                    model: new ContentTemplatesPanelModel({
                        blockData: getFlatBlocksData(contentTemplatesData)
                    }),
                    collection: editor.Blocks.getCategories(),
                    editor,
                    panelId: 'views-container'
                });
            }

            this.toggle();
        },

        stop() {
            this.toggle(false);
        }
    });

    editor.templateContentApiAccessor = new ApiAccessor({
        http_method: 'GET',
        route: 'oro_cms_content_template_content'
    });

    Panels.addButton('views', {
        id: 'content-templates',
        attributes: {
            title: __('oro.cms.wysiwyg.content_template_plugin.button_title')
        },
        className: 'fa fa-book',
        command: 'toggle-content-templates-panel',
        order: 40
    });
});
