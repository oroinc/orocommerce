import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import openDigitalAssetsCommand from 'orocms/js/app/grapesjs/modules/open-digital-assets-command';
import createRegistry from 'orocms/js/app/grapesjs/utils/create-registry';
import LinkTypeModel from './link-type-model';
import LinkTypeView from './link-type-view';
import linkStyle from './variants/link-variant';
import buttonStyle from './variants/button-variant';

/**
 * Module-level references to the active registry instances.
 * Used by isComponent() which is called as a static function without context.
 * Updated each time a LinkType builder initializes.
 */
let activeStyleRegistry = null;

const LinkType = BaseType.extend({
    parentType: 'link',

    TypeModel: LinkTypeModel,
    TypeView: LinkTypeView,

    commands: {
        'open-digital-assets': openDigitalAssetsCommand
    },

    constructor: function LinkType(options) {
        LinkType.__super__.constructor.call(this, options);
    },

    onInit() {
        this.styleRegistry = createRegistry({
            order: 100,
            classes: [],
            droppable: false,
            defaultComponents: [],
            traits: [],
            toolbarItems: []
        });
        this.editor.LinkStyleRegistry = this.styleRegistry;
        activeStyleRegistry = this.styleRegistry;

        this.registerStyles();
        this.registerFileStyleManagerType();
    },

    /**
     * Register all default link styles.
     */
    registerStyles() {
        [linkStyle, buttonStyle].forEach(style => {
            if (!this.styleRegistry.has(style.id)) {
                this.styleRegistry.register(style);
            }
        });
    },

    /**
     * Register file style manager type for background-image file picking.
     */
    registerFileStyleManagerType() {
        this.editor.StyleManager.addType(
            'file',
            {
                openAssetManager() {
                    this.em.get('Commands').run(
                        'open-digital-assets',
                        {
                            target: this.model,
                            title: __('oro.cms.wysiwyg.digital_asset.image.title'),
                            routeName: 'oro_digital_asset_widget_choose_image',
                            onSelect: this.onSelect.bind(this)
                        }
                    );
                },

                onSelect(digitalAssetModel) {
                    this.model.upValue(digitalAssetModel.get('previewMetadata').url);
                }
            }
        );
    },

    isComponent(el) {
        if (el.nodeType !== el.ELEMENT_NODE || el.tagName !== 'A') {
            return false;
        }

        const styleId = activeStyleRegistry
            ? activeStyleRegistry.detectFromElement(el) || 'link'
            : 'link';

        return {
            type: 'link',
            linkStyle: styleId
        };
    },

    /**
     * Create a single Link block on the panel.
     */
    createPanelButton() {
        if (!this.editor.ComponentRestriction.isAllow(['a'])) {
            return;
        }

        const {Blocks} = this.editor;
        const blocks = Blocks.getAll();

        blocks.comparator = 'order';

        const existingBlock = Blocks.get('link');
        const blockConfig = {
            id: 'link',
            category: 'Basic',
            label: __('oro.cms.wysiwyg.component.link.label'),
            select: true,
            attributes: {
                'class': 'fa fa-link'
            },
            content: {
                type: 'link',
                linkStyle: 'link'
            },
            order: 30
        };

        if (existingBlock) {
            existingBlock.set(blockConfig);
        } else {
            blocks.add(blockConfig);
        }

        blocks.sort();
    },

    registerEditorCommands() {
        if (this.editor.Commands.has('open-digital-assets')) {
            return;
        }

        LinkType.__super__.registerEditorCommands.call(this);
    }
}, {
    type: 'link',
    priority: 250
});

export default LinkType;
