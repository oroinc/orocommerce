define(function(require) {
    'use strict';

    const _ = require('underscore');
    const DialogWidget = require('oro/dialog-widget');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const template = require('tpl-loader!orocms/templates/grapesjs-content-block.html');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');

    /**
     * Content block component
     */
    const ContentBlockComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function ContentBlockComponent(options) {
            ContentBlockComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            const ComponentId = 'content-block';
            const domComps = options.DomComponents;
            const commands = options.Commands;
            const dType = domComps.getType('default');
            const dModel = dType.model;
            const dView = dType.view;
            const datagridName = 'cms-content-block-grid';
            const contentBlockAlias = options.Config.contentBlockAlias;

            options.BlockManager.add(ComponentId, {
                id: ComponentId,
                label: _.__('oro.cms.wysiwyg.component.content_block'),
                category: 'Basic',
                attributes: {
                    'class': 'fa fa-file-text-o'
                },
                content: {
                    type: ComponentId
                }
            });

            commands.add('content-block-settings', function(editor, sender, event) {
                const routeParams = {
                    gridName: datagridName
                };

                const dialog = new DialogWidget({
                    title: _.__('oro.cms.wysiwyg.content_block.title'),
                    url: routing.generate(
                        'oro_datagrid_widget',
                        routeParams
                    ),
                    dialogOptions: {
                        modal: true,
                        resizable: true,
                        autoResize: true,
                        close: function() {
                            if (event.cid && !event.get('contentBlock')) {
                                event.remove();
                            }
                        }
                    }
                });

                dialog.on('contentLoad', function(data, widget) {
                    const gridWidget = widget.componentManager.get(datagridName);
                    gridWidget.grid.columns.remove(_.last(gridWidget.grid.columns.models));

                    if (contentBlockAlias) {
                        excludeRow(gridWidget.grid);

                        gridWidget.data.options.totalRecords = 1;
                        gridWidget.grid.on('content:update', _.bind(excludeRow, this, gridWidget.grid));
                    }
                });

                dialog.on('grid-row-select', function(data) {
                    let sel = editor.getSelected();
                    if (event.cid) {
                        sel = event;
                    }

                    if (contentBlockAlias === data.model.get('alias')) {
                        mediator.execute('showFlashMessage', 'error', _.__('oro.cms.wysiwyg.exclude_content_block'), {
                            container: dialog.widget,
                            insertMethod: 'prependTo'
                        });
                        return;
                    }

                    sel.set('contentBlock', data.model);
                    dialog.remove();
                });

                dialog.render();
            });

            function excludeRow(grid) {
                const excluded = grid.collection.findWhere({
                    alias: contentBlockAlias
                });

                if (excluded) {
                    excluded.set({
                        row_class_name: 'row-disabled'
                    });
                }
            }

            function isToolbarComandExist(toolbar, command) {
                return _.findIndex(toolbar, {
                    command: command
                }) !== -1;
            }

            domComps.addType(ComponentId, {
                model: dModel.extend({
                    defaults: _.extend({}, dModel.prototype.defaults, {
                        type: ComponentId,
                        tagName: 'div',
                        classes: ['content-block', 'content-placeholder'],
                        contentBlock: null,
                        droppable: false
                    }),
                    constructor: function ContentBlockComponentModel(...args) {
                        dModel.prototype.constructor.apply(this, args);
                    },
                    initialize: function(o, opt, ...rest) {
                        dModel.prototype.initialize.call(this, o, opt, ...rest);

                        const toolbar = this.get('toolbar');

                        if (!isToolbarComandExist(toolbar, 'content-block-settings')) {
                            toolbar.unshift({
                                attributes: {
                                    'class': 'fa fa-gear'
                                },
                                command: 'content-block-settings'
                            });

                            this.set('toolbar', toolbar);
                        }

                        this.listenTo(this, 'change:contentBlock', this.onContentBlockChange, this);

                        options.off('canvas:drop').once('canvas:drop', function(DataTransfer, model) {
                            options.Commands.run('content-block-settings', model);
                        });
                    },

                    onContentBlockChange: function(model, contentBlock) {
                        this.set('attributes', {
                            'data-title': contentBlock.get('title')
                        });

                        this.set('content', '{{ content_block("' + contentBlock.get('alias') + '") }}');
                        this.view.render();
                    }
                }, {
                    isComponent: function(el) {
                        let result = '';
                        if (el.tagName === 'DIV' && el.className.indexOf('content-block') !== -1) {
                            result = {
                                type: ComponentId
                            };
                        }

                        return result;
                    }
                }),
                view: dView.extend({
                    onRender: function() {
                        const contentBlock = this.model.get('contentBlock');

                        if (contentBlock) {
                            this.$el.html(template({
                                title: contentBlock.cid ? contentBlock.get('title') : contentBlock.title
                            }));
                        } else {
                            this.$el.html(template({
                                title: this.$el[0].getAttribute('data-title')
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

    return ContentBlockComponent;
});
