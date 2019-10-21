define(function(require) {
    'use strict';

    var _ = require('underscore');
    var ContentBlockComponent = require('orocms/js/app/grapesjs/components/content-block');
    var ContentWidgetComponent = require('orocms/js/app/grapesjs/components/content-widget');
    var TableComponents = require('orocms/js/app/grapesjs/components/table');
    var TableResponsiveComponent = require('orocms/js/app/grapesjs/components/table-responsive');
    var LinkButtonComponent = require('orocms/js/app/grapesjs/components/link-button');
    var CodeComponent = require('orocms/js/app/grapesjs/components/code');
    var selectTemplate = require('tpl-loader!orocms/templates/grapesjs-select-action.html');

    /**
     * Create component manager
     * @param options
     * @constructor
     */
    var ComponentManager = function(options) {
        _.extend(this, _.pick(options, [
            'builder', 'excludeContentBlockAlias', 'excludeContentWidgetAlias'
        ]));

        this.init();
    };

    /**
     * Component manager methods
     * @type {{
     *  BlockManager: null,
     *  Commands: null,
     *  DomComponents: null,
     *  init: init,
     *  addComponents: addComponents,
     *  sortActionsRte: sortActionsRte
     *  }}
     */
    ComponentManager.prototype = {
        /**
         * @property {Object}
         */
        BlockManager: null,

        /**
         * @property {Object}
         */
        Commands: null,

        /**
         * @property {Object}
         */
        DomComponents: null,

        /**
         * @property {Object}
         */
        RichTextEditor: null,

        editorFormats: [
            'formatBlock',
            'insertOrderedList',
            'insertUnorderedList',
            'subscript',
            'superscript'
        ],

        /**
         * Create manager
         */
        init: function(options) {
            _.extend(this, _.pick(
                this.builder,
                [
                    'BlockManager',
                    'Commands',
                    'ComponentRestriction',
                    'DomComponents',
                    'RichTextEditor',
                    'editorFormats'
                ]
            ));

            this.addComponents();
            this.addActionRte();
        },

        /**
         * Add Rich Text Editor actions
         */
        addActionRte: function() {
            _.each(this.editorFormats, function(format) {
                this.RichTextEditor.remove(format);
            }, this);

            this.RichTextEditor.add('formatBlock', {
                icon: selectTemplate({
                    options: {
                        normal: 'Normal text',
                        h1: 'Heading 1',
                        h2: 'Heading 2',
                        h3: 'Heading 3',
                        h4: 'Heading 4',
                        h5: 'Heading 5',
                        h6: 'Heading 6'
                    },
                    name: 'tag'
                }),
                event: 'change',

                attributes: {
                    'title': 'Text format',
                    'class': 'gjs-rte-action text-format-action'
                },

                priority: 0,

                result: function result(rte, action) {
                    var value = action.btn.querySelector('[name="tag"]').value;

                    if (value === 'normal') {
                        var parentNode = rte.selection().getRangeAt(0).startContainer.parentNode;
                        var text = parentNode.innerText;
                        parentNode.remove();

                        return rte.insertHTML(text);
                    }
                    return rte.exec('formatBlock', value);
                },

                update: function(rte, action) {
                    var value = rte.doc.queryCommandValue(action.name);
                    var select = action.btn.querySelector('[name="tag"]');

                    if (value !== 'false') {
                        if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].indexOf(value) !== -1) {
                            select.value = value;
                        } else {
                            select.value = 'normal';
                        }
                    }
                }
            });

            this.RichTextEditor.add('insertOrderedList', {
                icon: '<i class="fa fa-list-ol"></i>',
                attributes: {
                    title: 'Ordered List'
                },
                result: function result(rte, action) {
                    return rte.exec('insertOrderedList');
                }
            });

            this.RichTextEditor.add('insertUnorderedList', {
                icon: '<i class="fa fa-list-ul"></i>',
                attributes: {
                    title: 'Unordered List'
                },
                result: function result(rte, action) {
                    return rte.exec('insertUnorderedList');
                }
            });

            this.RichTextEditor.add('subscript', {
                icon: '<i class="fa fa-subscript"></i>',
                attributes: {
                    title: 'Subscript'
                },
                result: function result(rte, action) {
                    return rte.exec('subscript');
                }
            });

            this.RichTextEditor.add('superscript', {
                icon: '<i class="fa fa-superscript"></i>',
                attributes: {
                    title: 'Superscript'
                },
                result: function result(rte, action) {
                    return rte.exec('superscript');
                }
            });
        },

        /**
         * Add components
         */
        addComponents: function() {
            new TableComponents(this.builder);
            new TableResponsiveComponent(this.builder);
            new LinkButtonComponent(this.builder);
            new CodeComponent(this.builder);
            new ContentBlockComponent(this.builder, {
                exclude: this.excludeContentBlockAlias
            });
            new ContentWidgetComponent(this.builder, {
                exclude: this.excludeContentWidgetAlias
            });
        }
    };

    return ComponentManager;
});
