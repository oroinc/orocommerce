import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';
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
const ContentBlockTypeBuilder = BaseTypeBuilder.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.content_block'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-file-text-o'
        }
    },

    editorEvents: {
        'canvas:drop': 'onDrop'
    },

    commands: {
        'content-block-settings': (editor, sender, componentModel) => {
            const contentBlockAlias = editor.Config.contentBlockAlias;
            const datagridName = 'cms-content-block-grid';
            const container = editor.Commands.isActive('fullscreen') ? editor.getEl() : 'body';

            const dialog = new DialogWidget({
                title: __('oro.cms.wysiwyg.content_block.title'),
                url: routing.generate(
                    'oro_datagrid_widget',
                    _.extend(editor.Config.requestParams, {gridName: datagridName})
                ),
                loadingElement: container,
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    autoResize: true,
                    appendTo: container,
                    close: function() {
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

    modelMixin: {
        defaults: {
            tagName: 'div',
            classes: ['content-block', 'content-placeholder'],
            contentBlock: null,
            droppable: false
        },

        initialize(...args) {
            this.constructor.__super__.initialize.call(this, ...args);

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

            this.set('content', '{{ content_block("' + contentBlock.get('alias') + '") }}');
            this.view.render();
        }
    },

    viewMixin: {
        onRender() {
            let title;
            const contentBlock = this.model.get('contentBlock');

            if (contentBlock) {
                title = contentBlock.cid ? contentBlock.get('title') : contentBlock.title;
            } else {
                title = this.$el.attr('data-title');
            }

            this.$el.html(template({title}));
        }
    },

    /**
     * @inheritdoc
     */
    constructor: function ContentBlockTypeBuilder(options) {
        ContentBlockTypeBuilder.__super__.constructor.call(this, options);
    },

    onDrop(DataTransfer, model) {
        if (model instanceof this.Model) {
            this.editor.runCommand('content-block-settings', model);
        }
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'DIV' && el.classList.contains('content-block')) {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default ContentBlockTypeBuilder;
