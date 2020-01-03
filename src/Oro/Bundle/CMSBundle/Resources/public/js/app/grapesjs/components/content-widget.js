define(function(require) {
    'use strict';

    const _ = require('underscore');
    const DialogWidget = require('oro/dialog-widget');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const template = require('tpl-loader!orocms/templates/grapesjs-content-widget.html');
    const routing = require('routing');

    /**
     * Insert string into string via position
     * @param str
     * @param insert
     * @param startOffset
     * @param endOffset
     * @returns {string}
     */
    function insetIntoString(str, insert, startOffset, endOffset) {
        return [str.slice(0, startOffset), insert, str.slice(endOffset)].join('');
    }

    /**
     * Check is toolbar has command ID
     * @param toolbar
     * @param command
     * @returns {boolean}
     */
    function isToolbarCommandExist(toolbar, command) {
        return _.findIndex(toolbar, {
            command: command
        }) !== -1;
    }

    /**
     * Content widget component
     */
    const ContentWidgetComponent = BaseComponent.extend({

        editor: null,

        RichTextEditor: null,

        Commands: null,

        /**
         * @inheritDoc
         */
        constructor: function ContentWidgetComponent(options) {
            ContentWidgetComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.editor = options;
            this.RichTextEditor = options.RichTextEditor;
            this.Commands = options.Commands;
            const ComponentId = 'content-widget';
            const domComps = options.DomComponents;
            const commands = options.Commands;
            const dType = domComps.getType('default');
            const dModel = dType.model;
            const dView = dType.view;

            options.on('component:selected', component => {
                if (component.attributes.type === 'content-widget') {
                    options.RichTextEditor.actionbar.hidden = true;
                }
            });

            options.on('component:deselected', component => {
                if (component.attributes.type === 'content-widget') {
                    options.RichTextEditor.actionbar.hidden = false;
                }
            });

            commands.add('content-widget-settings', (editor, sender, event) => {
                const datagridName = 'cms-block-content-widget-grid';

                const routeParams = {
                    gridName: datagridName
                };

                const dialog = new DialogWidget({
                    title: _.__('oro.cms.wysiwyg.content_widget.title'),
                    url: routing.generate(
                        'oro_datagrid_widget',
                        routeParams
                    ),
                    loadingElement: options.getEl(),
                    dialogOptions: {
                        modal: true,
                        resizable: true,
                        autoResize: true,
                        appendTo: options.getEl(),
                        close: function() {
                            if (event.cid && !event.get('contentWidget')) {
                                event.remove();
                            }
                        }
                    }
                });

                dialog.on('contentLoad', (data, widget) => {
                    const gridWidget = widget.componentManager.get(datagridName);
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
            });

            commands.add('inline-content-widget-settings', (editor, sender, event) => {
                const datagridName = 'cms-inline-content-widget-grid';

                const routeParams = {
                    gridName: datagridName
                };

                const dialog = new DialogWidget({
                    title: _.__('oro.cms.wysiwyg.content_widget.title'),
                    url: routing.generate(
                        'oro_datagrid_widget',
                        routeParams
                    ),
                    loadingElement: options.getEl(),
                    dialogOptions: {
                        modal: true,
                        resizable: true,
                        autoResize: true,
                        appendTo: options.getEl()
                    }
                });

                dialog.on('grid-row-select', data => {
                    const sel = editor.getSelected();

                    if (event && event.selection) {
                        const originalText = event.selection.anchorNode.innerHTML;

                        const offset = originalText.indexOf(event.nodeValue);

                        if (offset > 0) {
                            event.offset += offset;
                            event.extentOffset += offset;
                        }

                        sel.components(
                            insetIntoString(
                                originalText
                                , `<span 
                                data-title="${data.model.get('name')}" 
                                data-type="${data.model.get('widgetType')}" 
                                class="content-widget-inline"
                                >{{ widget("${data.model.get('name')}") }}</span>`
                                , event.offset, event.extentOffset
                            )
                        );
                    } else {
                        sel.onContentBlockChange(sel, data.model);
                    }

                    dialog.remove();
                });

                dialog.render();
            });

            domComps.addType(ComponentId, {
                model: dModel.extend({
                    defaults: _.extend({}, dModel.prototype.defaults, {
                        type: ComponentId,
                        tagName: 'div',
                        classes: ['content-widget', 'content-placeholder'],
                        contentWidget: null,
                        droppable: false,
                        editable: false,
                        stylable: false
                    }),
                    constructor: function ContentWidgetComponent(...args) {
                        dModel.prototype.constructor.apply(this, args);
                    },
                    initialize: function(o, opt, ...rest) {
                        if (this.get('tagName') === 'span') {
                            this.set('draggable', false);
                        }

                        dModel.prototype.initialize.call(this, o, opt, ...rest);

                        const toolbar = this.get('toolbar');
                        this.commandName = this.get('tagName') === 'span'
                            ? 'inline-content-widget-settings'
                            : 'content-widget-settings';

                        if (!isToolbarCommandExist(toolbar, this.commandName)) {
                            toolbar.unshift({
                                attributes: {
                                    'class': 'fa fa-gear',
                                    'label': _.__('oro.cms.wysiwyg.toolbar.widgetSetting')
                                },
                                command: this.commandName
                            });

                            this.set('toolbar', toolbar);
                        }

                        this.listenTo(this, 'change:contentWidget', this.onContentBlockChange, this);

                        options.off('canvas:drop').once('canvas:drop', (DataTransfer, model) => {
                            if (model.is(ComponentId)) {
                                options.Commands.run(this.commandName, model);
                            }
                        });
                    },

                    onContentBlockChange: function(model, contentWidget) {
                        this.set('attributes', {
                            'data-title': contentWidget.get('name'),
                            'data-type': contentWidget.get('widgetType')
                        });

                        this.set('content', '{{ widget("' + contentWidget.get('name') + '") }}');
                        this.view.render();
                    }
                }, {
                    isComponent: function(el) {
                        let result = '';
                        if (
                            (el.tagName === 'DIV' || el.tagName === 'SPAN') &&
                            el.className.indexOf('content-widget') !== -1
                        ) {
                            result = {
                                type: ComponentId
                            };
                        }

                        return result;
                    }
                }),
                view: dView.extend({
                    events: {
                        dblclick: 'onDoubleClick'
                    },

                    onRender: function() {
                        const contentWidget = this.model.get('contentWidget');

                        if (contentWidget) {
                            this.$el.html(template({
                                title: contentWidget.cid ? contentWidget.get('name') : contentWidget.name,
                                widgetType: contentWidget.cid
                                    ? contentWidget.get('widgetType')
                                    : contentWidget.widgetType,
                                inline: this.$el.prop('tagName') === 'SPAN'
                            }));
                        } else {
                            this.$el.html(template({
                                title: this.$el[0].getAttribute('data-title'),
                                widgetType: this.$el[0].getAttribute('data-type'),
                                inline: this.$el.prop('tagName') === 'SPAN'
                            }));
                        }
                    },

                    constructor: function ContentBlockComponentView(...args) {
                        dView.prototype.constructor.apply(this, args);
                    },

                    onDoubleClick: function(e) {
                        options.Commands.run(this.model.commandName);
                        e && e.stopPropagation();
                    }
                })
            });

            this.inlineEditing();
            this.createComponentButton();
        },

        inlineEditing() {
            this.RichTextEditor.remove('inlineWidget');
            this.RichTextEditor.add('inlineWidget', {
                icon: '<i class="fa fa-object-ungroup"></i>',
                attributes: {
                    title: 'Inline widget'
                },
                result: rte => {
                    const selection = rte.selection();
                    const offset = rte.selection().anchorOffset;
                    const extentOffset = rte.selection().extentOffset;
                    const nodeValue = rte.selection().anchorNode.nodeValue;

                    this.Commands.run('inline-content-widget-settings', {
                        selection,
                        offset,
                        extentOffset,
                        nodeValue
                    });
                }
            });
        },

        createComponentButton: function() {
            if (this.editor.ComponentRestriction.isAllow([
                'div'
            ])) {
                this.editor.BlockManager.add('content-widget', {
                    id: 'content-widget',
                    label: _.__('oro.cms.wysiwyg.component.content_widget'),
                    category: 'Basic',
                    attributes: {
                        'class': 'fa fa-object-ungroup'
                    },
                    content: {
                        type: 'content-widget'
                    }
                });
            }
        }
    });

    return ContentWidgetComponent;
});
