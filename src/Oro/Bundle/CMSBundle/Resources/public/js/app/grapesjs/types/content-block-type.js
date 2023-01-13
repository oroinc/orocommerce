import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import DialogWidget from 'oro/dialog-widget';
import template from 'tpl-loader!orocms/templates/grapesjs-content-block.html';
import mediator from 'oroui/js/mediator';
import routing from 'routing';

function excludeRow(grid, contentBlockAlias) {
    const excluded = grid.collection.findWhere({
        alias: contentBlockAlias
    });

    if (excluded) {
        excluded.set({
            row_class_name: 'row-disabled'
        });
    }
}

/**
 * Content block type builder
 */
const ContentBlockType = BaseType.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.content_block'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-file-text-o'
        },
        activate: true
    },

    commands: {
        'content-block-settings': (editor, sender, componentModel) => {
            const contentBlockAlias = editor.Config.contentBlockAlias;
            const datagridName = 'cms-content-block-grid';

            const dialog = new DialogWidget({
                title: __('oro.cms.wysiwyg.content_block.title'),
                url: routing.generate(
                    'oro_datagrid_widget',
                    _.extend(editor.Config.requestParams, {gridName: datagridName})
                ),
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    autoResize: true,
                    close() {
                        if (componentModel.cid && !componentModel.get('contentBlock')) {
                            componentModel.remove();
                        }
                    }
                }
            });

            dialog.on('contentLoad', function(data, widget) {
                const gridWidget = widget.componentManager.get(datagridName);
                gridWidget.grid.columns.remove(_.last(gridWidget.grid.columns.models));

                if (contentBlockAlias) {
                    excludeRow(gridWidget.grid, contentBlockAlias);

                    gridWidget.data.options.totalRecords = 1;
                    gridWidget.grid.on('content:update', excludeRow.bind(null, gridWidget.grid, contentBlockAlias));
                }
            });

            dialog.on('grid-row-select', function(data) {
                let selected = editor.getSelected();

                if (componentModel.cid) {
                    selected = componentModel;
                }

                if (contentBlockAlias === data.model.get('alias')) {
                    mediator.execute('showFlashMessage', 'error', __('oro.cms.wysiwyg.exclude_content_block'), {
                        container: dialog.widget,
                        insertMethod: 'prependTo'
                    });
                    return;
                }

                selected.set('contentBlock', data.model);
                dialog.remove();
            });

            dialog.render();
        }
    },

    modelProps: {
        defaults: {
            tagName: 'div',
            classes: ['content-block', 'content-placeholder'],
            contentBlock: null,
            droppable: false,
            name: 'Content Block'
        },

        init() {
            const toolbar = this.get('toolbar');
            const commandExists = _.some(toolbar, {
                command: 'content-block-settings'
            });

            if (!commandExists) {
                toolbar.unshift({
                    attributes: {
                        'class': 'fa fa-gear',
                        'label': __('oro.cms.wysiwyg.toolbar.blockSetting')
                    },
                    command: 'content-block-settings'
                });

                this.set('toolbar', toolbar);
            }

            this.listenTo(this, 'change:contentBlock', this.onContentBlockChange, this);
        },

        onContentBlockChange(model, contentBlock) {
            this.set('attributes', {
                'data-title': contentBlock.get('title')
            });

            const contentBlockExp = `{{ content_block("${contentBlock.get('alias')}") }}`;
            if (this.findType('textnode').length) {
                this.findType('textnode')[0].set('content', contentBlockExp);
            } else {
                this.set('content', contentBlockExp);
            }

            this.view.render();
        }
    },

    viewProps: {
        events: {
            dblclick: 'onActive'
        },

        onRender() {
            let title;
            const contentBlock = this.model.get('contentBlock');

            if (contentBlock) {
                title = contentBlock.cid ? contentBlock.get('title') : contentBlock.title;
            } else {
                title = this.$el.attr('data-title');
            }

            this.$el.html(template({title}));
        },

        onActive(event) {
            this.em.get('Commands').run('content-block-settings', this.model);

            event && event.stopPropagation();
        }
    },

    /**
     * @inheritdoc
     */
    constructor: function ContentBlockTypeBuilder(options) {
        ContentBlockTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        return el.nodeType === el.ELEMENT_NODE && el.tagName === 'DIV' && el.classList.contains('content-block');
    }
}, {
    type: 'content-block',
    options: {
        optionNames: ['excludeContentBlockAlias']
    }
});

export default ContentBlockType;
