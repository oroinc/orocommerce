define(function(require) {
    'use strict';

    const _ = require('underscore');
    const ContentBlockComponent = require('orocms/js/app/grapesjs/components/content-block');
    const DigitalAssetsComponent = require('orocms/js/app/grapesjs/components/digital-assets');
    const ContentWidgetComponent = require('orocms/js/app/grapesjs/components/content-widget');
    const TableComponents = require('orocms/js/app/grapesjs/components/table');
    const TableResponsiveComponent = require('orocms/js/app/grapesjs/components/table-responsive');
    const LinkButtonComponent = require('orocms/js/app/grapesjs/components/link-button');
    const CodeComponent = require('orocms/js/app/grapesjs/components/code');
    const TextBasicComponent = require('orocms/js/app/grapesjs/components/text-basic');
    const selectTemplate = require('tpl-loader!orocms/templates/grapesjs-select-action.html');

    /**
     * Create component manager
     * @param options
     * @constructor
     */
    const ComponentManager = function(options) {
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
            'link',
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
                    const value = action.btn.querySelector('[name="tag"]').value;

                    if (value === 'normal') {
                        const parentNode = rte.selection().getRangeAt(0).startContainer.parentNode;
                        const text = parentNode.innerText;
                        parentNode.remove();

                        return rte.insertHTML(text);
                    }
                    return rte.exec('formatBlock', value);
                },

                update: function(rte, action) {
                    const value = rte.doc.queryCommandValue(action.name);
                    const select = action.btn.querySelector('[name="tag"]');

                    if (value !== 'false') {
                        if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].indexOf(value) !== -1) {
                            select.value = value;
                        } else {
                            select.value = 'normal';
                        }
                    }
                }
            });

            this.RichTextEditor.add('link', {
                icon: '<i class="fa fa-link"></i>',
                name: 'link',
                attributes: {
                    title: 'Link'
                },
                result: rte => {
                    const anchor = rte.selection().anchorNode;
                    const nextSibling = anchor && anchor.nextSibling;
                    if (nextSibling && nextSibling.nodeName === 'A') {
                        rte.exec('unlink');
                    } else {
                        rte.insertHTML(`<a class="link" href="">${rte.selection()}</a>`);
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
            new DigitalAssetsComponent(this.builder);
            new TextBasicComponent(this.builder);
        }
    };

    return ComponentManager;
});
