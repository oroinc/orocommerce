define(function(require) {
    'use strict';

    var DigitalAssetsComponent;
    var _ = require('underscore');
    var routing = require('routing');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DigitalAssetDialogWidget = require('orodigitalasset/js/widget/digital-asset-dialog-widget');
    var DigitalAssetImageComponent = require('orocms/js/app/grapesjs/components/digital-asset-image');
    var DigitalAssetFileComponent = require('orocms/js/app/grapesjs/components/digital-asset-file');
    var DigitalAssetPropertyFileType = require('orocms/js/app/grapesjs/components/digital-asset-property-file-type');

    /**
     * Digital assets component
     * - adds open-digital-assets command
     * - adds file dom component type
     * - overrides image dom component type
     * - overrides file property type command
     */
    DigitalAssetsComponent = BaseComponent.extend({
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

                constructor: function OpenDigitalAssetsCommand() {
                    OpenDigitalAssetsCommand.__super__.constructor.apply(this, arguments);
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

                    this.dialog = this._openChooseDialog();
                    this.dialog.on('grid-row-select', this._onGridRowSelect.bind(this));
                    this.dialog.render();

                    return this;
                },

                stop: function(editor) {
                    this.dialog.remove();

                    return this;
                },

                /**
                 * @param {object} data
                 * @private
                 */
                _onGridRowSelect: function(data) {
                    this.options.onSelect(data.model, this);

                    this.dialog.remove();
                },

                _openChooseDialog: function() {
                    return new DigitalAssetDialogWidget({
                        title: this.options.title,
                        url: routing.generate(this.options.routeName, this.options.routeParams || {})
                    });
                }
            });
        }
    });

    return DigitalAssetsComponent;
});
