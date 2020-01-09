import $ from 'jquery';
import _ from 'underscore';
import grapesJS from 'grapesjs';

import BaseView from 'oroui/js/app/views/base/view';
import styleManagerModule from 'orocms/js/app/grapesjs/modules/style-manager-module';
import PanelManagerModule from 'orocms/js/app/grapesjs/modules/panels-module';
import DevicesModule from 'orocms/js/app/grapesjs/modules/devices-module';
import mediator from 'oroui/js/mediator';
import canvasStyle from 'orocms/js/app/grapesjs/modules/canvas-style';

import 'grapesjs-preset-webpage';
import 'orocms/js/app/grapesjs/plugins/components/grapesjs-components';
import 'orocms/js/app/grapesjs/plugins/import/import';
import {escapeWrapper} from 'orocms/js/app/grapesjs/plugins/grapesjs-style-isolation';
import ContentParser from 'orocms/js/app/grapesjs/plugins/grapesjs-content-parser';

/**
 * Create grapesJS content builder
 * @type {*|void}
 */
const GrapesjsEditorView = BaseView.extend({
    /**
     * @inheritDoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'autoRender', 'allow_tags', 'builderOptions', 'builderPlugins', 'currentTheme', 'canvasConfig',
        'contextClass', 'storageManager', 'stylesInputSelector', 'storagePrefix', 'themes',
        'propertiesInputSelector'
    ]),

    /**
     * @inheritDoc
     */
    autoRender: true,

    /**
     * Active style theme for iframe
     * @property {String}
     */
    activeTheme: null,

    /**
     * @property {grapesJS.Instance}
     */
    builder: null,

    /**
     * Allow html tags
     * @property {Object}
     */
    allow_tags: null,

    /**
     * Page context class
     * @property {String}
     */
    contextClass: 'body cms-page cms-typography',

    /**
     * State of changes in editor
     * @property {Boolean}
     */
    componentUpdated: false,

    /**
     * Main builder options
     * @property {Object}
     */
    builderOptions: {
        height: '2000px',
        avoidInlineStyle: true,
        avoidFrameOffset: true,
        allowScripts: 1,

        /**
         * Color picker options
         * @property {Object}
         */
        colorPicker: {
            appendTo: 'body',
            showPalette: false
        }
    },

    /**
     * Storage prefix
     * @property {String}
     */
    storagePrefix: 'gjs-',

    /**
     * Storage options
     * @property {Object}
     */
    storageManager: {
        autosave: false,
        autoload: false
    },

    /**
     * Canvas options
     * @property {Object}
     */
    canvasConfig: {
        canvasCss: canvasStyle
    },

    /**
     * Style manager options
     * @property {Object}
     */
    styleManager: {
        clearProperties: 1
    },

    /**
     * Asset manager settings
     * @property {Object}
     */
    assetManagerConfig: {
        embedAsBase64: 1
    },

    /**
     * Themes list
     * @property {Array}
     */
    themes: [],

    /**
     * Styles input selector
     * @property {String}
     */
    stylesInputSelector: '[data-grapesjs-styles]',

    /**
     * Styles input element
     * @property {Object}
     */
    $stylesInputElement: null,

    /**
     * Properties input selector
     * @property {String}
     */
    propertiesInputSelector: '[data-grapesjs-properties]',

    /**
     * Properties input element
     * @property {Object}
     */
    $propertiesInputElement: null,

    /**
     * @property {String}
     */
    wrapperSelector: '.page-content-editor, .fallback-item-value, .variant-collection',

    /**
     * @property {Array}
     */
    JSONcomponents: [],

    /**
     * @property {jQuery.Element}
     */
    $parent: null,

    /**
     * List of grapesjs plugins
     * @property {Object}
     */
    builderPlugins: {
        'gjs-preset-webpage': {
            aviaryOpts: false,
            filestackOpts: null,
            blocksBasicOpts: {
                flexGrid: 1
            },
            navbarOpts: false,
            countdownOpts: false,
            modalImportContent: function(editor) {
                return editor.getHtml() + '<style>' + editor.getCss() + '</style>';
            },
            importViewerOptions: {}
        },
        'grapesjs-components': {},
        'grapesjs-style-isolation': {},
        'grapesjs-import': {}
    },

    events: {
        'wysiwyg:enable': 'enableEditor',
        'wysiwyg:disable': 'disableEditor'
    },

    /**
     * @inheritDoc
     */
    constructor: function GrapesjsEditorView(options) {
        GrapesjsEditorView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     * @param options
     */
    initialize: function(options) {
        this.setCurrentContentAlias();
        this.$parent = this.$el.closest(this.wrapperSelector);
        this.$stylesInputElement = this.$parent.find(this.stylesInputSelector);
        this.$propertiesInputElement = this.$parent.find(this.propertiesInputSelector);
        this.setAlternativeFields();
        this.setActiveTheme(this.getCurrentTheme());

        if (this.allow_tags) {
            this.builderPlugins['grapesjs-components'] = _.extend({},
                this.builderPlugins['grapesjs-components'],
                {
                    allowTags: this.allow_tags
                }
            );
        }

        GrapesjsEditorView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritDoc
     */
    render: function() {
        this.applyComponentsJSON();
        this.initContainer();
        this.initBuilder();
    },

    /**
     * @inheritDoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        if (this._panelManagerModule) {
            this._panelManagerModule.dispose();
            delete this._panelManagerModule;
        }

        if (this._devicesModule) {
            this._devicesModule.dispose();
            delete this._devicesModule;
        }

        this.disableEditor();
        GrapesjsEditorView.__super__.dispose.call(this);
    },

    /**
     * Set disable editor
     */
    disableEditor: function() {
        if (this.builder) {
            this.builderUndelegateEvents();
            this.builder.destroy();

            this.disposeElements();

            this.builder = null;
        }
    },

    /**
     * Set enable editor
     */
    enableEditor: function() {
        if (!this.builder) {
            this.render();
        }
    },

    disposeElements: function() {
        this.$el.show();
        this.$container.remove();
    },

    /**
     * Creates editor container
     * @returns {*}
     */
    initContainer: function() {
        this.$container = $('<div class="grapesjs" data-skip-input-widgets />');
        this.$container.insertAfter(this.$el);
    },

    /**
     * Get properties json
     * @returns {Array}
     */
    applyComponentsJSON: function() {
        const value = this.$propertiesInputElement.val();

        this.JSONcomponents = value ? JSON.parse(value) : [];

        return this.JSONcomponents;
    },

    /**
     * Initialize builder instance
     */
    initBuilder: function() {
        this.builder = grapesJS.init(_.extend(
            {}
            , {
                avoidInlineStyle: 1,
                container: this.$container.get(0),
                components: !_.isEmpty(this.JSONcomponents)
                    ? this.JSONcomponents
                    : escapeWrapper(this.$el.val())
            }
            , this._prepareBuilderOptions()));

        // Ensures all changes to sectors, properties and types are applied.
        this.builder.StyleManager.getSectors().reset(styleManagerModule);

        this.builder.setIsolatedStyle(
            this.$stylesInputElement.val()
        );

        mediator.trigger('grapesjs:created', this.builder);

        this.builderDelegateEvents();
    },

    /**
     * Add builder event listeners
     */
    builderDelegateEvents: function() {
        this.$el.closest('form')
            .on(
                'keyup' + this.eventNamespace() + ' keypress' + this.eventNamespace()
                , e => {
                    const keyCode = e.keyCode || e.which;
                    if (keyCode === 13 && this.$container.get(0).contains(e.target)) {
                        e.preventDefault();
                        return false;
                    }
                })
            .on('submit', this.contentValidate.bind(this));

        this.builder.on('load', this._onLoadBuilder.bind(this));
        this.builder.on('update', this._onUpdatedBuilder.bind(this));
        this.builder.on('component:update', _.debounce(this._onComponentUpdatedBuilder.bind(this), 100));
        this.builder.on('changeTheme', this._updateTheme.bind(this));
        this.builder.on('component:selected', this.componentSelected.bind(this));

        this.builder.editor.view.$el.find('.gjs-toolbar')
            .off('mouseover')
            .on('mouseover', '.gjs-toolbar-item', e => {
                $(e.target).tooltip({
                    title: $(e.target).attr('label') || ''
                });

                $(e.target).tooltip('show');
            });

        // Fix reload form when click export to zip dialog
        this.builder.on('run:export-template', () => {
            $(this.builder.Modal.getContentEl())
                .find('.gjs-btn-prim').on('click', e => {
                    e.preventDefault();
                });
        });
    },

    /**
     * Remove builder event listeners
     */
    builderUndelegateEvents: function() {
        this.$el.closest('form').off();
        mediator.off('dropdown-button:click');

        if (this.builder) {
            this.builder.off();
            this.builder.editor.view.$el.find('.gjs-toolbar').off('mouseover');
        }
    },

    /**
     * Get current theme
     * @returns {Object}
     */
    getCurrentTheme: function() {
        return _.find(this.themes, function(theme) {
            return theme.active;
        });
    },

    /**
     * Set active state for button
     * @param panel {String}
     * @param name {String}
     */
    setActiveButton: function(panel, name) {
        this.builder.Commands.run(name);
        const button = this.builder.Panels.getButton(panel, name);

        button.set('active', true);
    },

    setCurrentContentAlias: function() {
        this.form = this.$el.closest('form');
        const contentBlockAliasField = this.form.find('[name="oro_cms_content_block[alias]"]');
        if (contentBlockAliasField.length && contentBlockAliasField.val()) {
            this.builderOptions.contentBlockAlias = contentBlockAliasField.val();
        }
    },

    setAlternativeFields: function() {
        const fieldPrefix = this.$el.attr('data-name');
        const styleFiledName = fieldPrefix + '-style';
        const propertiesFiledName = fieldPrefix + '-properties';

        if (!this.$stylesInputElement.length) {
            this.$stylesInputElement = $('[data-name="' + styleFiledName + '"]');
        }

        if (!this.$propertiesInputElement.length) {
            this.$propertiesInputElement = $('[data-name="' + propertiesFiledName + '"]');
        }
    },

    /**
     * Validation by tags
     * @param e {Object}
     */
    contentValidate: function(e) {
        if (!this.allow_tags) {
            return;
        }
        const _res = this.builder.ComponentRestriction.validate(
            this.builder.getIsolatedHtml(this.$el.val())
        );
        const validationMessage = _.__('oro.cms.wysiwyg.validation.import', {tags: _res.join(', ')});

        if (_res.length) {
            e.preventDefault();
            mediator.execute('showFlashMessage', 'error', validationMessage, {
                delay: 5000
            });

            return false;
        }
    },

    /**
     * Get editor content
     * @returns {String}
     */
    getEditorContent: function() {
        return this.builder.getIsolatedHtml();
    },

    /**
     * Get editor styles
     * @returns {String}
     */
    getEditorStyles: function() {
        return this.builder.getIsolatedCss();
    },

    /**
     * Get editor components
     * @returns {Object}
     */
    getEditorComponents: function() {
        const comps = this.builder.getComponents();

        return comps.length ? JSON.stringify(this.builder.getComponents()) : '';
    },

    componentSelected(model) {
        let toolbar = model.get('toolbar');

        toolbar = toolbar.map(tool => {
            switch (tool.command) {
                case 'select-parent':
                    tool.attributes.label = _.__('oro.cms.wysiwyg.toolbar.selectParent');
                    break;
                case 'tlb-move':
                    tool.attributes.label = _.__('oro.cms.wysiwyg.toolbar.move');
                    break;
                case 'tlb-clone':
                    tool.attributes.label = _.__('oro.cms.wysiwyg.toolbar.clone');
                    break;
                case 'tlb-delete':
                    tool.attributes.label = _.__('oro.cms.wysiwyg.toolbar.delete');
                    break;
            }

            return tool;
        });

        model.set('toolbar', toolbar);
    },

    /**
     * Add wrapper classes for iframe with content
     */
    _addClassForFrameWrapper: function() {
        $(this.builder.Canvas.getFrameEl().contentDocument).find('#wrapper').addClass(this.contextClass);
    },

    /**
     * Onload builder handler
     * @private
     */
    _onLoadBuilder: function() {
        this._panelManagerModule = new PanelManagerModule({
            builder: this.builder,
            themes: this.themes
        });

        this._devicesModule = new DevicesModule({builder: this.builder});

        this.setActiveButton('options', 'sw-visibility');
        this.setActiveButton('views', 'open-blocks');
        this._addClassForFrameWrapper();

        mediator.trigger('grapesjs:loaded', this.builder);
        mediator.trigger('page:afterChange');
    },

    /**
     * Update builder handler
     * @private
     */
    _onUpdatedBuilder: function() {
        mediator.trigger('grapesjs:updated', this.builder);
    },

    /**
     * Update components builder handler
     * @param state
     * @private
     */
    _onComponentUpdatedBuilder: function(state) {
        if (!this.componentUpdated) {
            mediator.on('dropdown-button:click', this._onComponentUpdatedBuilder, this);
        }
        this._updateInitialField();
        this.builder.trigger('change:canvasOffset');
        mediator.trigger('grapesjs:components:updated', state);
        this.componentUpdated = true;
    },

    /**
     * Update theme view in grapes iframe
     * @param selected {String}
     * @private
     */
    _updateTheme: function(selected) {
        if (!_.isUndefined(this.activeTheme) && this.activeTheme.name === selected) {
            this.setActiveTheme(selected);
            return false;
        }

        this.setActiveTheme(selected);
        this.builder.activeDevice = this.builder.getDevice();

        _.each(this.themes, function(theme) {
            theme.active = theme.name === selected;
        });

        const activeTheme = this.activeTheme;
        const head = this.builder.Canvas.getFrameEl().contentDocument.head;
        const style = head.querySelector('link');
        const styleClone = style.cloneNode();

        styleClone.setAttribute('href', this.activeTheme.stylesheet);
        styleClone.onload = function(e) {
            style.remove();
            mediator.trigger('grapesjs:theme:change', activeTheme);
        };

        head.appendChild(styleClone);
    },

    /**
     * Set active theme name
     * @param theme {String}
     * @private
     */
    setActiveTheme: function(theme) {
        this.activeTheme = _.find(this.themes, function(theme) {
            return theme.active;
        });
    },

    /**
     * Update source textarea and styles
     * @private
     */
    _updateInitialField: function() {
        const htmlContent = this.getEditorContent();
        const cssContent = this.getEditorStyles();
        const jsonContent = this.getEditorComponents();

        if (this.$el.val() !== htmlContent) {
            this.$el.val(htmlContent).trigger('change');
        }

        if (this.$stylesInputElement.val() !== cssContent) {
            this.$stylesInputElement.val(cssContent).trigger('change');
        }

        if (this.$propertiesInputElement.val() !== jsonContent) {
            this.$propertiesInputElement.val(jsonContent).trigger('change');
        }
    },

    /**
     * Collect and compare builder options
     * @returns {GrapesjsEditorView.builderOptions|{fromElement}}
     * @private
     */
    _prepareBuilderOptions: function() {
        _.extend(this.builderOptions
            , this._getPlugins()
            , this._getStorageManagerConfig()
            , this._getCanvasConfig()
            , this._getStyleManagerConfig()
            , this._getAssetConfig()
        );

        return this.builderOptions;
    },

    /**
     * Get extended Storage Manager config
     * @returns {{storageManager: (*|void)}}
     * @private
     */
    _getStorageManagerConfig: function() {
        return {
            storageManager: _.extend({}, this.storageManager, {
                id: this.storagePrefix
            })
        };
    },

    /**
     * Get extended Style Manager config
     * @returns {{styleManager: *}}
     * @private
     */
    _getStyleManagerConfig: function() {
        return {
            styleManager: this.styleManager
        };
    },

    /**
     * Get extended Canvas config
     * @returns {{canvasCss: string, canvas: {styles: (*|string)[]}}}
     * @private
     */
    _getCanvasConfig: function() {
        const theme = this.getCurrentTheme();
        return _.extend({}, this.canvasConfig, {
            canvas: {
                styles: [theme.stylesheet]
            },
            protectedCss: []
        });
    },

    /**
     * Get asset manager configuration
     * @returns {*|void}
     * @private
     */
    _getAssetConfig: function() {
        return {
            assetManager: this.assetManagerConfig
        };
    },

    /**
     * Get plugins list with options
     * @returns {{plugins: *, pluginsOpts: (GrapesjsEditorView.builderPlugins|{"gjs-preset-webpage"})}}
     * @private
     */
    _getPlugins: function() {
        return {
            plugins: [ContentParser, ...Object.keys(this.builderPlugins)],
            pluginsOpts: this.builderPlugins
        };
    }
});

export default GrapesjsEditorView;
