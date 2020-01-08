define(function(require) {
    'use strict';

    const _ = require('underscore');
    const routing = require('routing');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const DigitalAssetDialogWidget = require('orodigitalasset/js/widget/digital-asset-dialog-widget');
    const DigitalAssetImageComponent = require('orocms/js/app/grapesjs/components/digital-asset-image');
    const DigitalAssetFileComponent = require('orocms/js/app/grapesjs/components/digital-asset-file');
    const DigitalAssetPropertyFileType = require('orocms/js/app/grapesjs/components/digital-asset-property-file-type');

    /**
     * Digital assets component
     * - adds open-digital-assets command
     * - adds file dom component type
     * - overrides image dom component type
     * - overrides file property type command
     */
    const DigitalAssetsComponent = BaseComponent.extend({
        editor: null,

        constructor: function DigitalAssetsComponent(editor, options) {
            this.editor = editor;

            DigitalAssetsComponent.__super__.constructor.apply(this, [options || []]);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);

            this._addOpenAssetsCommand();

            new DigitalAssetPropertyFileType(this.editor);
            new DigitalAssetImageComponent(this.editor);
            new DigitalAssetFileComponent(this.editor);
        },

        /**
         * Adds open-digital-assets command which opens digital asset manager modal dialog.
         */
        _addOpenAssetsCommand: function() {
            const editor = this.editor;

            this.editor.Commands.add('open-digital-assets', {
                options: {
                    title: null,
                    routeName: null,
                    routeParams: null,
                    target: null,
                    onSelect: function() {
                    }
                },

                dialog: null,

                constructor: function OpenDigitalAssetsCommand(...args) {
                    OpenDigitalAssetsCommand.__super__.constructor.apply(this, args);
                },

                /**
                 * @param {object} editor
                 * @param {object} sender
                 * @param {object} options
                 * @returns {OpenDigitalAssetsCommand}
                 */
                run: function(editor, sender, options) {
                    this.options = options;

                    if (!options.routeName) {
                        throw new TypeError('Missing required option: routeName');
                    }

                    this.dialog = this._openChooseDialog(editor);
                    this.dialog.on('grid-row-select', this._onGridRowSelect.bind(this));
                    this.dialog.on('close', () => editor.stopCommand(this.id));
                    this.dialog.render();

                    return this;
                },

                stop: function(editor) {
                    this.dialog.dispose();
                    return this;
                },

                /**
                 * @param {object} data
                 * @private
                 */
                _onGridRowSelect: function(data) {
                    this.options.onSelect(data.model, this);
                    editor.stopCommand(this.id);
                },

                _openChooseDialog: function(editor) {
                    return new DigitalAssetDialogWidget({
                        title: this.options.title,
                        url: routing.generate(this.options.routeName, this.options.routeParams || {}),
                        loadingElement: editor.getEl(),
                        dialogOptions: {
                            appendTo: editor.getEl()
                        }
                    });
                }
            });
        }
    });

    return DigitalAssetsComponent;
});
