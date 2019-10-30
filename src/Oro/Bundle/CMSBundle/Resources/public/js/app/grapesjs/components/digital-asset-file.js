define(function(require) {
    'use strict';

    var DigitalAssetFileComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Adds "file" dom component type which uses digital assets manager.
     */
    DigitalAssetFileComponent = BaseComponent.extend({
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
                        var metadata = digitalAssetModel.get('previewMetadata');

                        digitalAssetFileComponentModel
                            .setAttributes({
                                href: '{{ wysiwyg_file('+metadata['digitalAssetId']+',"'+metadata['uuid']+'") }}',
                                title: metadata['title'] || ''
                            })
                            .set('content', metadata['filename'] || '');
                    }
                }
            );
        },

        _addComponentType: function() {
            var DefaultComponentType = this.editor.DomComponents.getType('link');
            var DefaultModel = DefaultComponentType.model;
            var DefaultView = DefaultComponentType.view;
            var digitalAssetFile = this;

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

                    constructor: function DigitalAssetFileComponentModel() {
                        DefaultModel.prototype.constructor.apply(this, arguments);
                    },

                    /**
                     * @param {Object} properties
                     * @param {Object} options
                     */
                    initialize: function(properties, options) {
                        DefaultModel.prototype.initialize.apply(this, arguments);

                        var toolbar = this.get('toolbar');
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
                    getAttrToHTML: function() {
                        var attr = DefaultModel.prototype.getAttrToHTML.apply(this, arguments);

                        _.each(['href', 'title'], (function(attributeName) {
                            var attributeValue = this.get(attributeName);
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
                        var result = '';
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

                    constructor: function DigitalAssetComponentView() {
                        DefaultView.prototype.constructor.apply(this, arguments);
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
                    updateAttributes: function() {
                        DefaultView.prototype.updateAttributes.apply(this, arguments);

                        this.$el.attr('href', '#');
                    }
                })

            });
        }
    });

    return DigitalAssetFileComponent;
});
