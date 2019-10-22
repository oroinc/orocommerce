define(function(require) {
    'use strict';

    var ContentBlockComponent;
    var _ = require('underscore');
    var DialogWidget = require('oro/dialog-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var template = require('tpl-loader!orocms/templates/grapesjs-content-block.html');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');

    /**
     * Content block component
     */
    ContentBlockComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function ContentBlockComponent() {
            ContentBlockComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var ComponentId = 'content-block';
            var domComps = options.DomComponents;
            var commands = options.Commands;
            var dType = domComps.getType('default');
            var dModel = dType.model;
            var dView = dType.view;
            var datagridName = 'cms-content-block-grid';
            var contentBlockAlias = options.Config.contentBlockAlias;

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
                var routeParams = {
                    gridName: datagridName
                };

                var dialog = new DialogWidget({
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
                    var gridWidget = widget.componentManager.get(datagridName);
                    gridWidget.grid.columns.remove(_.last(gridWidget.grid.columns.models));

                    if (contentBlockAlias) {
                        excludeRow(gridWidget.grid);

                        gridWidget.data.options.totalRecords = 1;
                        gridWidget.grid.on('content:update', _.bind(excludeRow, this, gridWidget.grid));
                    }
                });

                dialog.on('grid-row-select', function(data) {
                    var sel = editor.getSelected();
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
                var excluded = grid.collection.findWhere({
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
                    constructor: function ContentBlockComponentModel() {
                        dModel.prototype.constructor.apply(this, arguments);
                    },
                    initialize: function(o, opt) {
                        dModel.prototype.initialize.apply(this, arguments);

                        var toolbar = this.get('toolbar');

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
                        var result = '';
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
                        var contentBlock = this.model.get('contentBlock');

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
                    constructor: function ContentBlockComponentView() {
                        dView.prototype.constructor.apply(this, arguments);
                    }
                })
            });
        }
    });

    return ContentBlockComponent;
});
