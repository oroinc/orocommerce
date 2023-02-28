import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import grapesJS from 'grapesjs';
import tools from 'oroui/js/tools';

import BaseView from 'oroui/js/app/views/base/view';
import styleManagerModule from 'orocms/js/app/grapesjs/modules/style-manager-module';
import PanelManagerModule from 'orocms/js/app/grapesjs/modules/panels-module';
import DevicesModule from 'orocms/js/app/grapesjs/modules/devices-module';
import mediator from 'oroui/js/mediator';
import StateModel from 'orocms/js/app/grapesjs/modules/state-model';

import parserPostCSS from 'grapesjs-parser-postcss';
import RteEditorPlugin from 'orocms/js/app/grapesjs/plugins/oro-rte-editor';
import {escapeWrapper, getWrapperAttrs} from 'orocms/js/app/grapesjs/plugins/components/content-isolation';
import i18nMessages from 'orocms/js/app/grapesjs/plugins/i18n-messages';
import ContentParser from 'orocms/js/app/grapesjs/plugins/grapesjs-content-parser';
import CodeValidator from 'orocms/js/app/grapesjs/plugins/code-validator';
import moduleConfig from 'module-config';

const MIN_EDITOR_WIDTH = 1100;
const LOCK_PASTE_ATTR = 'data-lock-paste';

const config = {
    allowBreakpoints: [],
    disableDeviceManager: false,
    ...moduleConfig(module.id)
};

/**
 * Create grapesJS content builder
 * @type {*|void}
 */
const GrapesjsEditorView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'autoRender', 'allow_tags', 'allowed_iframe_domains', 'currentTheme', 'canvasConfig',
        'contextClass', 'storageManager', 'stylesInputSelector', 'storagePrefix', 'themes',
        'entityClass', 'disableDeviceManager', 'disableIsolation', 'extraStyles'
    ]),

    /**
     * @inheritdoc
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
     * Allow iframe domains
     * @property {Array}
     */
    allowed_iframe_domains: [],

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

    entityClass: null,

    /**
     * Main builder options
     * @property {Object}
     */
    builderOptions: {
        height: '700px',
        avoidInlineStyle: true,
        avoidFrameOffset: true,
        allowScripts: 1,
        pasteStyles: false,
        requestParams: {},
        noticeOnUnload: false,
        cssIcons: false,
        showDevices: false,
        log: tools.debug ? ['warning', 'error'] : [],
        selectorManager: {
            // This option allows to apply styles by id attribute, therefore will affect only actual element
            componentFirst: true
        },

        /**
         * Color picker options
         * @property {Object}
         */
        colorPicker: {
            appendTo: 'body',
            showPalette: false,
            chooseText: __('oro.cms.wysiwyg.color_picker.choose_text'),
            cancelText: __('oro.cms.wysiwyg.color_picker.cancel_text'),
            containerClassName: 'prevent-click-outside'
        },

        codeManager: {
            direction: _.isRTL() ? 'rtl' : 'ltr'
        },

        /**
         * Modal Export Title text
         */
        textViewCode: __('oro.cms.wysiwyg.export.title'),

        deviceManager: {
            devices: []
        },

        Parser: {
            returnArray: true
        },

        blockManager: {
            appendOnClick(block, editor) {
                const selected = editor.getSelected();

                const [model] = selected && selected.get('droppable') === true
                    ? selected.append(block.get('content'))
                    : editor.getWrapper().append(block.get('content'));

                if (block.get('activate')) {
                    editor.selectAdd(model);
                    model.trigger('active');
                }

                editor.Canvas.scrollTo(model, {
                    behavior: 'smooth'
                });
            }
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
     * Configurations for Trait Manager
     * @property {Object}
     */
    traitManager: {
        optionsTarget: [{
            value: '_self'
        }, {
            value: '_blank'
        }]
    },

    /**
     * Canvas options
     * @property {Object}
     */
    canvasConfig: {},

    /**
     * Style manager options
     * @property {Object}
     */
    styleManager: {
        clearProperties: true,
        sectors: []
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

    propertiesInputSelector: '[data-grapesjs-properties]',

    /**
     * Styles input element
     * @property {Object}
     */
    $stylesInputElement: null,

    /**
     * @property {String}
     */
    wrapperSelector: '.page-content-editor, .fallback-item-value, .content-variant-item',

    fallbackContainer: '.fallback-container',

    /**
     * @property {jQuery.Element}
     */
    $parent: null,

    /**
     * @property {Object}
     */
    rte: null,

    /**
     * Disable responsive design manager
     * @property {Boolean}
     */
    disableDeviceManager: config.disableDeviceManager,

    /**
     * Allow breakpoints list
     * @property {Array}
     */
    allowBreakpoints: config.allowBreakpoints,

    /**
     * Is editor enabled
     * @property {boolean}
     */
    enabled: false,

    /**
     * If editor init in fallback container
     * @property {boolean}
     */
    inFallbackContainer: false,

    /**
     * @property {boolean}
     */
    disableIsolation: false,

    /**
     * Extra styles for editor preview
     * @property {Array}
     */
    extraStyles: [],

    /**
     * List of grapesjs plugins
     * @property {Object}
     */
    builderPlugins: {
        'component-types-plugin': {},
        'grapesjs-export': {},
        'wysiwyg-settings': {},
        'sorter-hints': {},
        'grapesjs-components': {},
        'grapesjs-style-isolation': {},
        'grapesjs-import': {},
        'grapesjs-code': {},
        'grapesjs-panel-scrolling-hints': {},
        'grapesjs-code-mode': {}
    },

    events: {
        'wysiwyg:enable': 'throttleEnableEditor',
        'wysiwyg:disable': 'throttleDisableEditor'
    },

    listen: {
        'layout:reposition mediator': 'onLayoutReposition'
    },

    /**
     * @inheritdoc
     */
    constructor: function GrapesjsEditorView(options) {
        this.throttleEnableEditor = _.throttle(this.enableEditor.bind(this), 250);
        this.throttleDisableEditor = _.throttle(this.disableEditor.bind(this), 250);

        GrapesjsEditorView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     * @param options
     */
    initialize(options = {}) {
        this.builderOptions = {...this.builderOptions, ...options.builderOptions};
        this.setCurrentContentAlias();
        this.inFallbackContainer = !!this.$el.closest(this.fallbackContainer).length;
        this.$parent = this.$el.closest(this.wrapperSelector);
        this.$stylesInputElement = this.$parent.find(this.stylesInputSelector);
        this.$propertiesInputElement = this.$parent.find(this.propertiesInputSelector);

        this.setAlternativeFields();
        this.initStateModel();
        this.setActiveTheme(this.getCurrentTheme());

        const extendOptions = {};

        if (this.allow_tags) {
            extendOptions.allowTags = this.allow_tags;
        }

        if (this.allowed_iframe_domains) {
            extendOptions.allowedIframeDomains = this.allowed_iframe_domains;
        }

        if (options.builderPlugins) {
            this.builderPlugins = {
                ...this.builderPlugins,
                ...options.builderPlugins
            };
        }

        this.extendPluginOptions('component-types-plugin', options);
        this.extendPluginOptions('grapesjs-components', extendOptions);
        this.extendPluginOptions('grapesjs-import', {
            entityClass: this.entityClass,
            fieldName: this.$el.attr('data-grapesjs-field')
        });
        this.extendPluginOptions('grapesjs-export', {
            entityLabels: options.entityLabels
        });

        GrapesjsEditorView.__super__.initialize.call(this, options);
    },

    extendPluginOptions(pluginName, opts = {}) {
        if (!this.builderPlugins[pluginName]) {
            return;
        }

        this.builderPlugins[pluginName] = {
            ...this.builderPlugins[pluginName],
            ...opts
        };
    },

    /**
     * @inheritdoc
     */
    render() {
        this._deferredRender();
        this.renderStart = true;
        this.timeoutId = null;

        if (_.isMobile() || _.isTouchDevice()) {
            this.message = mediator.execute('showFlashMessage', 'error', __('oro.cms.wysiwyg.mobile.flash_message'), {
                container: this.$el.parent(),
                hideCloseButton: true
            });

            this.$el.parent().addClass('editor-unavailable');

            return;
        }

        this.initContainer();
        this.initBuilder();
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        this.disableEditor();

        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
        }
        GrapesjsEditorView.__super__.dispose.call(this);
    },

    timeoutEditor(callback) {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
        }
        this.timeoutId = setTimeout(() => callback(), 250);
    },

    /**
     * Set disable editor
     */
    disableEditor() {
        if (this.renderStart) {
            return this.timeoutEditor(this.disableEditor.bind(this));
        }

        if (!this.builder || !this.enabled) {
            return;
        }

        this.builder.trigger('destroy');
        this.builderUndelegateEvents();

        for (const command of Object.keys(this.builder.Commands.getActive())) {
            this.builder.Commands.stop(command);
        }

        if (this._panelManagerModule) {
            this._panelManagerModule.dispose();
            delete this._panelManagerModule;
        }

        if (this._devicesModule) {
            this._devicesModule.dispose();
            delete this._devicesModule;
        }

        this.builder.destroy();
        this.disposeElements();

        this.stopListening();

        this.builder = null;
        this.enabled = false;
    },

    /**
     * Set enable editor
     */
    enableEditor() {
        if (this.builder || this.enabled) {
            return;
        }

        if (this.renderStart && this.inFallbackContainer && !this.timeoutId) {
            return this.timeoutEditor(this.enableEditor.bind(this));
        }

        this.render();
    },

    disposeElements() {
        this.$el.show();
        this.$container.remove();
    },

    /**
     * Creates editor container
     * @returns {*}
     */
    initContainer() {
        this.$container = $('<div class="grapesjs" data-skip-input-widgets data-ignore-form-state-change />');
        this.$container.appendTo(this.$el.parent());
    },

    initStateModel() {
        let data = {};

        try {
            data = JSON.parse(this.$propertiesInputElement.val());
        } catch (e) {}

        this.state = new StateModel(data);
    },

    getState() {
        return this.state;
    },

    isStateChanged() {
        return JSON.stringify(this.state.toJSON()) === this.$propertiesInputElement.val();
    },

    /**
     * Initialize builder instance
     */
    initBuilder() {
        this.builder = grapesJS.init({
            avoidInlineStyle: 1,
            container: this.$container.get(0),
            ...this._prepareBuilderOptions()
        });

        this.builder.parentView = this;
        this.builder.getState = this.getState.bind(this);
        this.builder.getBreakpoints = this.getBreakpoints.bind(this);
        this.builder.StyleManager.getSectors().reset(styleManagerModule);

        this.builderDelegateEvents();

        this.rte = this.builder.RichTextEditor;
        if (_.isRTL()) {
            this.rtlFallback();
        }

        mediator.trigger('grapesjs:created', this.builder);
    },

    /**
     * Add builder event listeners
     */
    builderDelegateEvents() {
        const canvas = this.builder.Canvas;
        const $form = this.$el.closest('form');

        this.listenTo(this.builder, 'load', this._onLoadBuilder.bind(this));
        this.listenTo(this.builder, 'update', this._onUpdatedBuilder.bind(this));
        this.listenTo(this.builder, 'component:update', this._onComponentUpdatedBuilder.bind(this));
        this.listenTo(this.builder, 'changeTheme', this._updateTheme.bind(this));
        this.listenTo(this.builder, 'component:add', this.componentAdd.bind(this));
        this.listenTo(this.builder, 'component:selected', this.componentSelected.bind(this));
        this.listenTo(this.builder, 'component:deselected', this.componentDeselected.bind(this));
        this.listenTo(this.builder, 'component:remove:before', this.componentBeforeRemove.bind(this));
        this.listenTo(this.builder, 'component:remove', this.componentRemove.bind(this));
        this.listenTo(this.builder, 'rteToolbarPosUpdate', this.updateRtePosition.bind(this));
        this.listenTo(this.state, 'change', this.updatePropertyField.bind(this));

        // Fix reload form when click export to zip dialog
        this.listenTo(this.builder, 'run:export-template', () => {
            $(this.builder.Modal.getContentEl())
                .find('.gjs-btn-prim').on('click', e => {
                    e.preventDefault();
                });
        });

        $(this.builder.Canvas.getBody()).on(
            'paste',
            '[contenteditable="true"]',
            function(e) {
                // Prevent recursive call of "paste" event in IE11
                if (e.target.hasAttribute(LOCK_PASTE_ATTR)) {
                    e.target.removeAttribute(LOCK_PASTE_ATTR);
                } else if (!this.builderOptions.pasteStyles) {
                    e.preventDefault();

                    this.onPasteContent(e);
                }
            }.bind(this)
        );

        $form.on(`keyup${this.eventNamespace()} keydown${this.eventNamespace()}`, e => {
            const keyCode = e.keyCode || e.which;
            if (keyCode === 13 && this.$container.get(0).contains(e.target)) {
                e.preventDefault();
                return false;
            }
        });

        $(document).on(`mousedown${this.eventNamespace()}`, event => {
            const prevents = document.querySelectorAll('.prevent-click-outside, .ui-dialog, .modal');
            if ([...prevents].some(prevent => prevent.contains(event.target))) {
                return;
            }

            if (!this.builder.getContainer().contains(event.target)) {
                this.builder.getSelectedAll().forEach(selected => this.builder.selectRemove(selected));
            }
        });

        canvas.getCanvasView().$el.on(`scroll${this.eventNamespace()}`, e => {
            if (!this.enabled) {
                return;
            }
            const $cvTools = $(e.target).find('#gjs-cv-tools');

            $cvTools.css({
                top: e.target.scrollTop
            });

            // Force recalculate highlight boxes positions;
            this.builder.trigger('frame:updated', {
                frame: canvas.model.get('frame')
            });
        });

        this.$el.closest('.scrollable-container').on(`scroll${this.eventNamespace()}`, () => {
            if (this.enabled) {
                this.builder.trigger('change:canvasOffset');
            }
        });
    },

    /**
     * Remove builder event listeners
     */
    builderUndelegateEvents() {
        this.$el.closest('form').off(this.eventNamespace());
        this.$el.closest('.scrollable-container').off(this.eventNamespace());
        this.$stylesInputElement.off(this.eventNamespace());

        mediator.off('dropdown-button:click');

        const canvas = this.builder.Canvas;

        if (canvas) {
            canvas.getCanvasView().$el.off(this.eventNamespace());
            $(canvas.getBody()).off();
        }

        this.stopListening(this.builder);

        $(document).off(this.eventNamespace());

        if (this.builder) {
            this.builder.editor.view.$el.find('.gjs-toolbar').off('mouseover');
        }
    },

    onLayoutReposition() {
        if (this.builder) {
            this.builder.trigger('change:canvasOffset');
        }
    },

    /**
     * Get current theme
     * @returns {Object}
     */
    getCurrentTheme() {
        return _.find(this.themes, function(theme) {
            return theme.active;
        });
    },

    /**
     * Check if editor has enabled
     * @returns {boolean}
     */
    isEnabled() {
        return !!this.builder;
    },

    /**
     * Set active state for button
     * @param panel {String}
     * @param name {String}
     */
    setActiveButton(panel, name) {
        this.builder.Commands.run(name);
        const button = this.builder.Panels.getButton(panel, name);

        button.set('active', true);
    },

    setCurrentContentAlias() {
        this.form = this.$el.closest('form');
        const contentBlockAliasField = this.form.find('[name="oro_cms_content_block[alias]"]');
        if (contentBlockAliasField.length && contentBlockAliasField.val()) {
            this.builderOptions.contentBlockAlias = contentBlockAliasField.val();
        }
    },

    setAlternativeFields() {
        const fieldPrefix = this.$el.attr('data-ftid');
        const styleFiledName = fieldPrefix + '_style';
        const propertiesFiledName = fieldPrefix + '_properties';

        if (!this.$stylesInputElement.length) {
            this.$stylesInputElement = this.form.find(`[data-ftid="${styleFiledName}"]`);
        }

        if (!this.$propertiesInputElement.length) {
            this.$propertiesInputElement = this.form.find(`[data-ftid="${propertiesFiledName}"]`);
        }

        this.$stylesInputElement.attr('data-editor-field-name', this.$el.attr('name'));
        this.$propertiesInputElement.attr('data-editor-field-name', this.$el.attr('name'));
    },

    /**
     * Get editor content
     * @returns {String}
     */
    getEditorContent() {
        return this.disableIsolation ? this.builder.getHtml() : this.builder.getIsolatedHtml();
    },

    /**
     * Get editor styles
     * @returns {String}
     */
    getEditorStyles() {
        return this.disableIsolation ? this.builder.getCss() : this.builder.getIsolatedCss();
    },

    getToolbarItems() {
        return $(this.builder.editor.view.$el.find('.gjs-toolbar .gjs-toolbar-item'));
    },

    componentBeforeRemove(model) {
        model.trigger('model:remove:before', model);
    },

    componentRemove(model) {
        model.trigger('model:remove', model);
    },

    componentAdd(model) {
        if (model.get('type') === 'textnode' && model.parent()?.get('type') === 'wrapper') {
            model.replaceWith(`<div>${model.get('content')}</div>`);
        }
    },

    componentDeselected(model) {
        this.builder.editor.view.$el.find('.gjs-toolbar').off('mouseover');
        this.getToolbarItems().each(function() {
            const tooltip = $(this).data('bs.tooltip');

            if (tooltip) {
                tooltip.dispose();
            }
        });

        model.trigger('model:deselected', model);
        this.togglePrivateClasses(model, false);
    },

    componentSelected(model) {
        let toolbar = model.get('toolbar');
        if (_.isArray(toolbar)) {
            toolbar = toolbar.map(tool => {
                const attributes = tool.attributes || {};
                tool.label = '';
                if (_.isFunction(tool.command) && !attributes.label && !tool.id) {
                    attributes.label = __('oro.cms.wysiwyg.toolbar.selectParent');
                    attributes.class = 'fa fa-arrow-up';

                    tool.attributes = attributes;
                    return tool;
                }

                const name = tool.id || tool.command;
                switch (name) {
                    case 'tlb-move':
                        attributes.label = __('oro.cms.wysiwyg.toolbar.move');
                        attributes.class = 'fa fa-arrows';
                        break;
                    case 'tlb-clone':
                        attributes.label = __('oro.cms.wysiwyg.toolbar.clone');
                        attributes.class = 'fa fa-clone';
                        break;
                    case 'tlb-delete':
                        attributes.label = __('oro.cms.wysiwyg.toolbar.delete');
                        attributes.class = 'fa fa-trash';
                        break;
                }

                tool.attributes = attributes;
                return tool;
            });

            model.set('toolbar', toolbar);

            model.trigger('model:selected', model);
            this.builder.Panels.getButton('views', 'open-sm').set('active', true);
        }

        this.builder.editor.view.$el.find('.gjs-toolbar')
            .off('mouseover')
            .on('mouseover', '.gjs-toolbar-item', e => {
                $(e.target).tooltip({
                    title: $(e.target).attr('label') || ''
                });

                $(e.target).tooltip('show');
            });

        this.toggleSelectorManager(model);
        this.togglePrivateClasses(model, true);
    },

    /**
     * Toggle Selector Manager in current model doesn't support selector manager
     * @param {Backbone.Model} model
     */
    toggleSelectorManager(model) {
        const {SelectorManager} = this.builder;
        const styleManagerEl = SelectorManager.selectorTags.el;
        const messageContainer = document.createElement('div');
        messageContainer.classList.add('gjs-sm-header');
        messageContainer.innerText = __('oro.cms.wysiwyg.style_manager.unsupport');
        if (model.get('disableSelectorManager')) {
            styleManagerEl.style.display = 'none';
            if (!SelectorManager.selectorTags.messageContainer) {
                styleManagerEl.after(messageContainer);
                SelectorManager.selectorTags.messageContainer = messageContainer;
            }
        } else {
            styleManagerEl.style.display = '';
            if (SelectorManager.selectorTags.messageContainer) {
                SelectorManager.selectorTags.messageContainer.remove();
                delete SelectorManager.selectorTags.messageContainer;
            }
        }
    },

    togglePrivateClasses(model, state) {
        const selectors = this.builder.Selectors.getSelected();
        const privateClasses = model.get('privateClasses') || [];

        selectors.forEach(selector => privateClasses.includes(selector.get('name')) && selector.set('private', state));
    },

    /**
     * Add wrapper classes for iframe with content
     */
    _addClassForFrameWrapper() {
        $(this.builder.Canvas.getFrameEl().contentDocument).find('body').addClass(this.contextClass);
    },

    onPasteContent(e) {
        if (e.originalEvent.clipboardData) {
            const content = e.originalEvent.clipboardData.getData('text/plain');

            e.target.ownerDocument.execCommand('insertText', false, content);
        } else if (window.clipboardData) {
            const data = window.clipboardData.getData('Text');
            let content;

            try {
                content = JSON.parse(data).content;
            } catch (e) {
                content = data;
            }

            e.target.setAttribute(LOCK_PASTE_ATTR, '');
            e.target.ownerDocument.execCommand('paste', false, content);
        }
    },

    /**
     * Onload builder handler
     * @private
     */
    _onLoadBuilder() {
        this._panelManagerModule = new PanelManagerModule({
            builder: this.builder,
            themes: this.themes
        });

        if (!this.disableDeviceManager) {
            this._devicesModule = this.builder._devicesModule = new DevicesModule({
                builder: this.builder,
                allowBreakpoints: this.allowBreakpoints
            });
        }

        this.setActiveButton('views', 'open-blocks');
        this._addClassForFrameWrapper();

        mediator.trigger('grapesjs:loaded', this.builder);

        this.$el.closest('.ui-dialog-content').dialog('option', 'minWidth', MIN_EDITOR_WIDTH);

        if (this.$el.valid()) {
            this.builder.setComponents(escapeWrapper(this.$el.val()));
            this.builder.setStyle(this.builder.getPureStyleString(this.$stylesInputElement.val()));
        }

        const wrapperAttrs = getWrapperAttrs(this.$el.val());
        if (!_.isEmpty(wrapperAttrs)) {
            wrapperAttrs.class && this.builder.getWrapper().addClass(wrapperAttrs.class);
        }

        this.enabled = true;
        _.delay(() => {
            this.renderStart = false;
            this.builder.trigger('editor:rendered');
            this._resolveDeferredRender();
        }, 250);
    },

    /**
     * Update builder handler
     * @private
     */
    _onUpdatedBuilder() {
        mediator.trigger('grapesjs:updated', this.builder);
        this._updateInitialField();
    },

    /**
     * Update components builder handler
     * @param state
     * @private
     */
    _onComponentUpdatedBuilder(state) {
        if (!this.componentUpdated) {
            mediator.on('dropdown-button:click', this._onComponentUpdatedBuilder, this);
        }
        this._updateInitialField();
        mediator.trigger('grapesjs:components:updated', state);
        this.componentUpdated = true;
    },

    /**
     * Update theme view in grapes iframe
     * @param selected {String}
     * @private
     */
    _updateTheme(selected) {
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
        styleClone.onload = function() {
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
    setActiveTheme(theme) {
        this.activeTheme = _.find(this.themes, function(theme) {
            return theme.active;
        });
    },

    /**
     * Update source textarea and styles
     * @private
     */
    _updateInitialField() {
        if (!this.builder || this.builder.CodeValidator.isInvalid() || this.renderStart) {
            return;
        }

        const htmlContent = this.getEditorContent();
        const cssContent = this.getEditorStyles();

        if (this.$el.val() !== htmlContent) {
            this.$el.val(htmlContent).trigger('change');

            if (this.$el.hasClass('error')) {
                this.$el.valid();
            }
        }

        if (this.$stylesInputElement.val() !== cssContent) {
            this.$stylesInputElement.val(cssContent).trigger('change');

            if (this.$stylesInputElement.hasClass('error')) {
                this.$stylesInputElement.valid();
            }
        }

        this.updatePropertyField();
    },

    updatePropertyField() {
        if (this.isStateChanged() || this.renderStart) {
            return;
        }

        this.$propertiesInputElement.val(JSON.stringify(this.state.toJSON()));
    },

    getCanvasStylesheets() {
        if (!this.extraStyles) {
            return [];
        }

        return this.extraStyles.reduce((styles, {name, url}) => {
            if (name === 'canvas') {
                styles.push(url);
                return styles;
            }
        }, []);
    },

    /**
     * Collect and compare builder options
     * @returns {GrapesjsEditorView.builderOptions|{fromElement}}
     * @private
     */
    _prepareBuilderOptions() {
        _.extend(this.builderOptions
            , this._getPlugins()
            , this._getStorageManagerConfig()
            , this._getCanvasConfig()
            , this._getStyleManagerConfig()
            , this._getTaitManagerConfig()
            , this._getAssetConfig()
        );

        return this.builderOptions;
    },

    /**
     * Get extended Storage Manager config
     * @returns {{storageManager: (*|void)}}
     * @private
     */
    _getStorageManagerConfig() {
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
    _getStyleManagerConfig() {
        return {
            styleManager: this.styleManager
        };
    },

    /**
     * Get extended Tait Manager config
     * @returns {{traitManager: *}}
     * @private
     */
    _getTaitManagerConfig() {
        return {
            traitManager: this.traitManager
        };
    },

    /**
     * Get extended Canvas config
     * @returns {{canvasCss: string, canvas: {styles: (*|string)[]}}}
     * @private
     */
    _getCanvasConfig() {
        const theme = this.getCurrentTheme();
        const canvasStylesheets = this.getCanvasStylesheets();
        return _.extend({}, this.canvasConfig, {
            canvas: {
                styles: theme ? [theme.stylesheet, ...canvasStylesheets] : canvasStylesheets
            },
            protectedCss: []
        });
    },

    /**
     * Get asset manager configuration
     * @returns {*|void}
     * @private
     */
    _getAssetConfig() {
        return {
            assetManager: this.assetManagerConfig
        };
    },

    /**
     * Get plugins list with options
     * @returns {{plugins: *, pluginsOpts: (GrapesjsEditorView.builderPlugins|{"gjs-preset-webpage"})}}
     * @private
     */
    _getPlugins() {
        const pluginConfig = {
            plugins: [
                parserPostCSS,
                i18nMessages,
                CodeValidator,
                ContentParser,
                RteEditorPlugin,
                ...Object.keys(this.builderPlugins)
            ],
            pluginsOpts: this.builderPlugins
        };

        const priority = plugin => {
            if (typeof plugin === 'string') {
                plugin = grapesJS.plugins.get(plugin);
            }

            return plugin && (plugin.priority ?? 200);
        };

        pluginConfig.plugins.sort(
            (aPlugin, bPlugin) => priority(aPlugin) - priority(bPlugin)
        ).forEach(plugin => {
            if (typeof plugin === 'function') {
                plugin.bind({
                    editorView: this
                });
            }

            if (pluginConfig.pluginsOpts[plugin]) {
                pluginConfig.pluginsOpts[plugin]['editorView'] = this;
            }
        });

        return pluginConfig;
    },

    updateRtePosition(pos) {
        if (!this.builder) {
            return;
        }

        const $builderIframe = this.builder.Canvas.canvasView.$el;
        const {
            height: frameHeight,
            bottom: frameBottom,
            top: frameTop
        } = this.builder.Canvas.getFrameEl().getBoundingClientRect();
        const selected = this.builder.getSelected();

        if (!selected) {
            return;
        }

        const $el = selected.view.$el;
        const {
            width: targetWidth,
            height: targetHeight,
            top: targetTop,
            bottom: targetBottom
        } = this.rte.actionbar.getBoundingClientRect();

        $(this.rte.actionbar).parent().css('margin-left', '');

        if ($el && $builderIframe.innerWidth() <= (pos.canvasOffsetLeft + targetWidth)) {
            let marginLeft = $el.outerWidth() - targetWidth;
            $(this.rte.actionbar).parent().css('margin-left', marginLeft);

            const barOffset = $(this.rte.actionbar).offset().left - $builderIframe.offset().left;
            if (barOffset < 0) {
                marginLeft -= barOffset;
                $(this.rte.actionbar).parent().css('margin-left', marginLeft);
            }
        }

        if (pos.top < 0 && frameHeight > (pos.canvasOffsetTop + targetHeight)) {
            pos.top += $el.outerHeight() + targetHeight;
        }

        if (this.rte.customRte) {
            this.rte.customRte.toggleVisibility(targetTop > frameBottom || targetBottom < frameTop);
        }
    },

    rtlFallback() {
        this.builder.LayerManager.render = _.wrap(this.builder.LayerManager.render, function(wrap) {
            const root = wrap();

            root.querySelectorAll('[data-toggle-select]').forEach(el => {
                el.style.paddingRight = el.style.paddingLeft;
                el.style.paddingLeft = '';
            });
            return root;
        });
    },

    getBreakpoints(allowBreakpoints = []) {
        if (this.disableDeviceManager) {
            return [];
        }

        return this._devicesModule._getCSSBreakpoint(allowBreakpoints);
    }
});

export default GrapesjsEditorView;
