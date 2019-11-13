define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Adds "file" dom component type which uses digital assets manager.
     */
    const DigitalAssetFileComponent = BaseComponent.extend({
        editor: null,

        constructor: function DigitalAssetFileComponent(editor, options) {
            this.editor = editor;

            DigitalAssetFileComponent.__super__.constructor.apply(this, [options || []]);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this._addBlock();
            this._addComponentType();
        },

        _addBlock: function() {
            this.editor.BlockManager.add('file', {
                id: 'file',
                label: __('oro.cms.wysiwyg.component.digital_asset.file'),
                category: 'Basic',
                attributes: {
                    'class': 'fa fa-file-o'
                },
                content: {
                    type: 'file'
                }
            });
        },

        /**
         * @param {DigitalAssetFileComponentModel} digitalAssetFileComponentModel
         * @private
         */
        _openDigitalAssetManager: function(digitalAssetFileComponentModel) {
            this.editor.Commands.run(
                'open-digital-assets',
                {
                    target: digitalAssetFileComponentModel,
                    title: __('oro.cms.wysiwyg.digital_asset.file.title'),
                    routeName: 'oro_digital_asset_widget_choose_file',
                    onSelect: function(digitalAssetModel) {
                        const metadata = digitalAssetModel.get('previewMetadata');

                        digitalAssetFileComponentModel
                            .setAttributes({
                                href: '{{ wysiwyg_file('+metadata['digitalAssetId']+',\''+metadata['uuid']+'\') }}',
                                title: metadata['title'] || ''
                            })
                            .set('content', metadata['filename'] || '');
                    }
                }
            );
        },

        _addComponentType: function() {
            const DefaultComponentType = this.editor.DomComponents.getType('link');
            const DefaultModel = DefaultComponentType.model;
            const DefaultView = DefaultComponentType.view;
            const digitalAssetFile = this;

            digitalAssetFile.editor.DomComponents.addType('file', {
                model: DefaultModel.extend({
                    defaults: _.extend({}, DefaultModel.prototype.defaults, {
                        'type': 'file',
                        'tagName': 'a',
                        'classes': ['digital-asset-file'],
                        'activeOnRender': 1,
                        'void': 0,
                        'droppable': 1,
                        'editable': 1,
                        'highlightable': 0,
                        'resizable': 0,
                        'traits': ['title', 'target']
                    }),

                    constructor: function DigitalAssetFileComponentModel(...args) {
                        DefaultModel.prototype.constructor.apply(this, args);
                    },

                    /**
                     * @param {Object} properties
                     * @param {Object} options
                     * @param args
                     */
                    initialize: function(properties, options, ...args) {
                        DefaultModel.prototype.initialize.apply(this, [properties, options, ...args]);

                        const toolbar = this.get('toolbar');
                        if (_.findIndex(toolbar, {command: this._toolbarSettingsCommand.bind(this)}) === -1) {
                            toolbar.unshift({
                                attributes: {'class': 'fa fa-gear'},
                                command: this._toolbarSettingsCommand.bind(this)
                            });

                            this.set('toolbar', toolbar);
                        }
                    },

                    _toolbarSettingsCommand: function() {
                        digitalAssetFile._openDigitalAssetManager(this);
                    },

                    /**
                     * Returns object of attributes for HTML
                     * @return {Object}
                     * @private
                     */
                    getAttrToHTML: function(...args) {
                        const attr = DefaultModel.prototype.getAttrToHTML.apply(this, args);

                        _.each(['href', 'title'], (function(attributeName) {
                            const attributeValue = this.get(attributeName);
                            if (attributeValue) {
                                attr[attributeName] = attributeValue;
                            }
                        }).bind(this));

                        return attr;
                    },

                    /**
                     * @inheritDoc
                     */
                    isComponent: function(el) {
                        let result = '';
                        if (el.tagName === 'A' && el.className.indexOf('digital-asset-file') !== -1) {
                            result = {
                                type: 'file'
                            };
                        }
                        return result;
                    }
                }),

                view: DefaultView.extend({
                    tagName: 'a',

                    constructor: function DigitalAssetComponentView(...args) {
                        DefaultView.prototype.constructor.apply(this, args);
                    },

                    /**
                     * @param {object} e Event
                     */
                    onActive: function(e) {
                        e && e.stopPropagation();

                        this.openModal();
                    },

                    /**
                     * Opens dialog for file changing
                     * @private
                     */
                    openModal: function() {
                        digitalAssetFile._openDigitalAssetManager(this.model);
                    },

                    /**
                     * @inheritDoc
                     */
                    updateAttributes: function(...args) {
                        DefaultView.prototype.updateAttributes.apply(this, args);

                        this.$el.attr('href', '#');
                    }
                })

            });
        }
    });

    return DigitalAssetFileComponent;
});
