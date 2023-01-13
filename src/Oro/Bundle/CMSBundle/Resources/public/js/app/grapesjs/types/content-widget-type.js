import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import DialogWidget from 'oro/dialog-widget';
import template from 'tpl-loader!orocms/templates/grapesjs-content-widget.html';
import routing from 'routing';

function createDialog(gridName, editor, onClose) {
    const dialogOptions = {
        modal: true,
        resizable: true,
        autoResize: true
    };

    if (_.isFunction(onClose)) {
        dialogOptions.close = onClose;
    }

    return new DialogWidget({
        title: __('oro.cms.wysiwyg.content_widget.title'),
        url: routing.generate('oro_datagrid_widget', _.extend(editor.Config.requestParams, {gridName: gridName})),
        dialogOptions
    });
}

/**
 * Content widget type builder
 */
const ContentWidgetType = BaseType.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.content_widget'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-object-ungroup'
        },
        activate: true
    },

    commandName: null,

    editorEvents: {
        'component:selected': 'onSelect',
        'component:deselected': 'onDeselect'
    },

    modelProps: {
        defaults: {
            tagName: 'div',
            classes: ['content-widget', 'content-placeholder'],
            contentWidget: null,
            droppable: false,
            editable: false,
            stylable: false,
            name: 'Content Widget'
        },

        init() {
            if (this.get('tagName') === 'span') {
                this.set('textable', true);
                this.set('draggable', '[data-gjs-type="text"]');
                this.setClass('content-widget-inline');
            }

            const toolbar = this.get('toolbar');
            const commandName = this.getSettingsCommandName();
            const commandExists = _.some(toolbar, {
                command: commandName
            });

            if (!commandExists) {
                toolbar.unshift({
                    attributes: {
                        'class': 'fa fa-gear',
                        'label': __('oro.cms.wysiwyg.toolbar.widgetSetting')
                    },
                    command: commandName
                });

                this.set('toolbar', toolbar);
            }

            this.listenTo(this, 'change:contentWidget', this.onContentBlockChange, this);
        },

        onContentBlockChange(model, contentWidget) {
            this.set('attributes', {
                'data-title': contentWidget.get('name'),
                'data-type': contentWidget.get('widgetType')
            });

            const contentWidgetExp = `{{ widget("${contentWidget.get('name')}") }}`;
            if (this.findType('textnode').length) {
                this.findType('textnode')[0].set('content', contentWidgetExp);
            } else {
                this.set('content', contentWidgetExp);
            }

            this.view.render();
        },

        getSettingsCommandName() {
            return this.get('tagName') === 'span' ? 'inline-content-widget-settings' : 'content-widget-settings';
        }
    },

    viewProps: {
        events: {
            dblclick: 'onActive'
        },

        onRender() {
            const contentWidget = this.model.get('contentWidget');
            let {name: title, widgetType} = contentWidget || {};

            if (!contentWidget) {
                title = this.$el.attr('data-title');
                widgetType = this.$el.attr('data-type');
            } else if (contentWidget.cid) {
                title = contentWidget.get('name');
                widgetType = contentWidget.get('widgetType');
            }

            this.$el.html(template({
                inline: this.$el.prop('tagName') === 'SPAN',
                title,
                widgetType
            }));
        },

        onActive(event) {
            this.em.get('Commands').run(this.model.getSettingsCommandName(), this.model);

            event && event.stopPropagation();
        }
    },

    commands: {
        'content-widget-settings': (editor, sender, event) => {
            const gridName = 'cms-block-content-widget-grid';
            const dialog = createDialog(gridName, editor, function() {
                if (event.cid && !event.get('contentWidget')) {
                    event.remove();
                }
            });

            dialog.on('contentLoad', (data, widget) => {
                const gridWidget = widget.componentManager.get(gridName);
                gridWidget.grid.columns.remove(_.last(gridWidget.grid.columns.models));
            });

            dialog.on('grid-row-select', data => {
                let sel = editor.getSelected();
                if (event.cid) {
                    sel = event;
                }

                sel.set('contentWidget', data.model);
                dialog.remove();
            });

            dialog.render();
        },
        'inline-content-widget-settings': (editor, sender, {rte, selection}) => {
            const gridName = 'cms-inline-content-widget-grid';
            const dialog = createDialog(gridName, editor);

            dialog.on('grid-row-select', data => {
                const sel = editor.getSelected();
                if (selection.type !== 'None') {
                    rte.insertHTML(`<span
                        data-title="${data.model.get('name')}"
                        data-type="${data.model.get('widgetType')}"
                        class="content-widget-inline"
                        >{{ widget("${data.model.get('name')}") }}</span>`);
                    sel.syncContent();
                } else {
                    sel.onContentBlockChange(sel, data.model);
                }

                dialog.remove();
            });

            dialog.render();
        }
    },

    /**
     * @inheritdoc
     */
    constructor: function ContentWidgetTypeBuilder(options) {
        ContentWidgetTypeBuilder.__super__.constructor.call(this, options);
    },

    onInit() {
        this.editor.RteEditor.addAction({
            name: 'inlineWidget',
            order: 50,
            icon: '<span class="fa fa-object-ungroup" aria-hidden="true"></span>',
            group: 'widgets',
            attributes: {
                title: __('oro.cms.wysiwyg.simple_actions.inline_widget.title')
            },
            result: rte => {
                const selection = rte.selection();

                this.editor.runCommand('inline-content-widget-settings', {
                    selection,
                    rte
                });
            }
        });
    },

    onSelect(model) {
        if (this.isOwnModel(model)) {
            this.editor.RichTextEditor.actionbar.hidden = true;
        }
    },

    onDeselect(model) {
        if (this.isOwnModel(model)) {
            this.editor.RichTextEditor.actionbar.hidden = false;
        }
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE &&
            (el.classList.contains('content-widget') || el.classList.contains('content-widget-inline'));
    }
}, {
    type: 'content-widget',
    options: {
        optionNames: ['excludeContentWidgetAlias']
    }
});

export default ContentWidgetType;
