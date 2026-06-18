import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import {applyFileMetadata} from 'orocms/js/app/grapesjs/utils/file-metadata';
import BASE_TRAITS from './link-traits';
import {TEMP_ATTR} from './constants';

export default (BaseTypeModel, {editor}) => {
    const styleRegistry = editor.LinkStyleRegistry;

    const LinkTypeModel = BaseTypeModel.extend({
        editor,
        tempAttr: TEMP_ATTR,

        constructor: function LinkTypeModel(...args) {
            return LinkTypeModel.__super__.constructor.apply(this, args);
        },

        init() {
            LinkTypeModel.__super__.init.call(this);

            this.detectAndApply();
            this.listenTo(this, 'change:linkStyle', this.onStyleChange);
        },

        /**
         * Detect and apply the style from the current model state.
         */
        detectAndApply() {
            const currentStyle = this.get('linkStyle');

            if (!currentStyle || !styleRegistry.has(currentStyle)) {
                const detected = styleRegistry.detectFromModel(this) || 'link';

                this.set('linkStyle', detected, {silent: true});
            }

            this.applyStyle(this.get('linkStyle'), {initial: true});
        },

        /**
         * Handle style change from trait selector.
         */
        onStyleChange(model, newStyle) {
            const oldStyle = model.previous('linkStyle');

            if (oldStyle === newStyle) {
                return;
            }

            this.deactivateStyle(oldStyle);
            this.cleanupStyleClasses(oldStyle);
            this.applyStyle(newStyle, {initial: false});

            this.trigger('change:toolbar');
        },

        /**
         * Apply style configuration to the model.
         */
        applyStyle(styleId, options = {}) {
            const style = styleRegistry.get(styleId);

            if (!style) {
                return;
            }

            if (style.classes.length) {
                this.addClass(style.classes.join(' '));
            }

            this.set('droppable', style.droppable);
            this.updateTraitsForStyle(style);
            this.updateToolbarForStyle(style);

            if (style.onActivate) {
                style.onActivate(this);
            }

            if (!options.initial && style.defaultComponents.length && !this.get('components').length) {
                this.components(style.defaultComponents);
            }
        },

        /**
         * Deactivate current style.
         */
        deactivateStyle(styleId) {
            const style = styleRegistry.get(styleId);

            if (style) {
                this.removeConfigTraitsAndToolbar(style);
            }
        },

        /**
         * Remove CSS classes specific to the old style.
         */
        cleanupStyleClasses(styleId) {
            const style = styleRegistry.get(styleId);

            if (style && style.classes.length) {
                this.removeClass(style.classes.join(' '));
            }
        },

        /**
         * Update traits: keep base traits + add style-specific ones.
         */
        updateTraitsForStyle(style) {
            const styleTrait = this.getTrait('linkStyle');

            if (styleTrait) {
                styleTrait.set('options', styleRegistry.getSelectOptions());
            }

            style.traits.forEach(traitDef => {
                const name = traitDef.name || traitDef;

                if (!this.getTrait(name)) {
                    this.addTrait(traitDef);
                }
            });
        },

        /**
         * Update toolbar with style-specific items.
         */
        updateToolbarForStyle(style) {
            this.mergeToolbarItems(style.toolbarItems);
        },

        /**
         * Open the digital assets file picker and apply the chosen file to the link.
         */
        openFilePicker() {
            this.em.get('Commands').run(
                'open-digital-assets',
                {
                    target: this,
                    title: __('oro.cms.wysiwyg.digital_asset.file.title'),
                    routeName: 'oro_digital_asset_widget_choose_file',
                    onSelect: digitalAssetModel => {
                        applyFileMetadata(this, digitalAssetModel.get('previewMetadata'));
                    }
                }
            );
        },

        /**
         * Handle text trait change — sync with textnode child.
         */
        onLinkTextChange(model, value) {
            const content = _.escape(value);
            const [textnode] = this.findType('textnode');

            if (textnode) {
                textnode.replaceWith(content);
            } else if (content !== '') {
                this.append(content);
            }
        },

        /**
         * Merge toolbar items, adding only those not already present.
         * @param {Array} items Toolbar items to add
         */
        mergeToolbarItems(items) {
            if (!items.length) {
                return;
            }

            const toolbar = [...this.get('toolbar')];
            const newItems = items.filter(
                item => !toolbar.some(existing => existing.id === item.id)
            );

            if (newItems.length) {
                this.set('toolbar', [...newItems, ...toolbar]);
            }
        },

        /**
         * Remove traits and toolbar items defined by a style config, then call onDeactivate.
         * @param {Object} config Style config object
         */
        removeConfigTraitsAndToolbar(config) {
            if (config.onDeactivate) {
                config.onDeactivate(this);
            }

            const traitNames = config.traits.map(t => t.name || t);

            traitNames.forEach(name => this.removeTrait(name));

            if (config.toolbarItems.length) {
                const toolbar = this.get('toolbar').filter(
                    item => !config.toolbarItems.some(ti => ti.id === item.id)
                );

                this.set('toolbar', toolbar);
            }
        },

        /**
         * Sanitize attributes for HTML output.
         * GrapesJS __attrToString already escapes attribute values,
         * so only filtering is needed here.
         */
        getAttrToHTML() {
            const attrs = this.getAttributes();

            delete attrs.style;
            delete attrs.onmousedown;
            delete attrs[this.tempAttr];
            delete attrs.text;
            delete attrs.__p;

            Object.keys(attrs).forEach(name => {
                if (!attrs[name]) {
                    delete attrs[name];
                }
            });

            return attrs;
        }
    });

    Object.defineProperty(LinkTypeModel.prototype, 'defaults', {
        value: {
            ...LinkTypeModel.prototype.defaults,
            tagName: 'a',
            editable: false,
            linkStyle: 'link',
            traits: [...BASE_TRAITS],
            components: [{
                type: 'textnode',
                content: 'Link'
            }]
        }
    });

    return LinkTypeModel;
};
