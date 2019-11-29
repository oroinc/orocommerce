define(function(require) {
    'use strict';

    const _ = require('underscore');
    const DialogWidget = require('oro/dialog-widget');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const template = require('tpl-loader!orocms/templates/grapesjs-content-widget.html');
    const routing = require('routing');

    /**
     * Content widget component
     */
    const ContentWidgetComponent = BaseComponent.extend({
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
            const ComponentId = 'content-widget';
            const domComps = options.DomComponents;
            const commands = options.Commands;
            const dType = domComps.getType('default');
            const dModel = dType.model;
            const dView = dType.view;
            const datagridName = 'cms-block-content-widget-grid';

            options.BlockManager.add(ComponentId, {
                id: ComponentId,
                label: _.__('oro.cms.wysiwyg.component.content_widget'),
                category: 'Basic',
                attributes: {
                    'class': 'fa fa-object-ungroup'
                },
                content: {
                    type: ComponentId
                }
            });

            commands.add('content-widget-settings', function(editor, sender, event) {
                const routeParams = {
                    gridName: datagridName
                };

                const dialog = new DialogWidget({
                    title: _.__('oro.cms.wysiwyg.content_widget.title'),
                    url: routing.generate(
                        'oro_datagrid_widget',
                        routeParams
                    ),
                    dialogOptions: {
                        modal: true,
                        resizable: true,
                        autoResize: true,
                        close: function() {
                            if (event.cid && !event.get('contentWidget')) {
                                event.remove();
                            }
                        }
                    }
                });

                dialog.on('contentLoad', function(data, widget) {
                    const gridWidget = widget.componentManager.get(datagridName);
                    gridWidget.grid.columns.remove(_.last(gridWidget.grid.columns.models));
                });

                dialog.on('grid-row-select', function(data) {
                    let sel = editor.getSelected();
                    if (event.cid) {
                        sel = event;
                    }

                    sel.set('contentWidget', data.model);
                    dialog.remove();
                });

                dialog.render();
            });

            function isToolbarCommandExist(toolbar, command) {
                return _.findIndex(toolbar, {
                    command: command
                }) !== -1;
            }

            domComps.addType(ComponentId, {
                model: dModel.extend({
                    defaults: _.extend({}, dModel.prototype.defaults, {
                        type: ComponentId,
                        tagName: 'div',
                        classes: ['content-widget', 'content-placeholder'],
                        contentWidget: null,
                        droppable: false
                    }),
                    constructor: function ContentWidgetComponent(...args) {
                        dModel.prototype.constructor.apply(this, args);
                    },
                    initialize: function(o, opt, ...rest) {
                        dModel.prototype.initialize.call(this, o, opt, ...rest);

                        const toolbar = this.get('toolbar');

                        if (!isToolbarCommandExist(toolbar, 'content-widget-settings')) {
                            toolbar.unshift({
                                attributes: {
                                    'class': 'fa fa-gear'
                                },
                                command: 'content-widget-settings'
                            });

                            this.set('toolbar', toolbar);
                        }

                        this.listenTo(this, 'change:contentWidget', this.onContentBlockChange, this);

                        options.off('canvas:drop').once('canvas:drop', function(DataTransfer, model) {
                            options.Commands.run('content-widget-settings', model);
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
                        if (el.tagName === 'DIV' && el.className.indexOf('content-widget') !== -1) {
                            result = {
                                type: ComponentId
                            };
                        }

                        return result;
                    }
                }),
                view: dView.extend({
                    onRender: function() {
                        const contentWidget = this.model.get('contentWidget');

                        if (contentWidget) {
                            this.$el.html(template({
                                title: contentWidget.cid ? contentWidget.get('name') : contentWidget.name,
                                widgetType: contentWidget.cid
                                    ? contentWidget.get('widgetType')
                                    : contentWidget.widgetType
                            }));
                        } else {
                            this.$el.html(template({
                                title: this.$el[0].getAttribute('data-title'),
                                widgetType: this.$el[0].getAttribute('data-type')
                            }));
                        }
                    },
                    constructor: function ContentBlockComponentView(...args) {
                        dView.prototype.constructor.apply(this, args);
                    }
                })
            });
        }
    });

    return ContentWidgetComponent;
});
