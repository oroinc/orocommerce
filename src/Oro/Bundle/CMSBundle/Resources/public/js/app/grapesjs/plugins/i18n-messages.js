import __ from 'orotranslation/js/translator';
import localeSettings from 'orolocale/js/locale-settings';
import $ from 'jquery';

const locale = localeSettings.getLocale();
const messages = {
    [locale]: {
        assetManager: {
            addButton: __('oro.cms.wysiwyg.asset_manager.add_button'),
            inputPlh: __('oro.cms.wysiwyg.asset_manager.input_plh'),
            modalTitle: __('oro.cms.wysiwyg.asset_manager.modal_title'),
            uploadTitle: __('oro.cms.wysiwyg.asset_manager.upload_title')
        },
        blockManager: {
            labels: {
                // 'block-id': 'Block Label'
                'column1': __('oro.cms.wysiwyg.component.column1.label'),
                'column2': __('oro.cms.wysiwyg.component.column2.label'),
                'column3': __('oro.cms.wysiwyg.component.column3.label'),
                'column3-7': __('oro.cms.wysiwyg.component.column37.label'),
                'image': __('oro.cms.wysiwyg.component.image.label'),
                'video': __('oro.cms.wysiwyg.component.video.label'),
                'map': __('oro.cms.wysiwyg.component.map.label'),
                'link-block': __('oro.cms.wysiwyg.component.link_block.label'),
                'link-button': __('oro.cms.wysiwyg.component.link_button.label')
            },
            categories: {
                // 'category-id': 'Category Label'
                Basic: __('oro.cms.wysiwyg.block_manager.categories.basic'),
                Forms: __('oro.cms.wysiwyg.block_manager.categories.forms')
            }
        },
        panels: {
            buttons: {
                titles: {
                    'sw-visibility': __('oro.cms.wysiwyg.option_panel.show_borders'),
                    'preview': __('oro.cms.wysiwyg.option_panel.preview'),
                    'fullscreen': __('oro.cms.wysiwyg.option_panel.fullscreen'),
                    'export-template': __('oro.cms.wysiwyg.option_panel.export'),
                    'undo': __('oro.cms.wysiwyg.option_panel.undo'),
                    'redo': __('oro.cms.wysiwyg.option_panel.redo'),
                    'gjs-open-import-webpage': __('oro.cms.wysiwyg.option_panel.import'),
                    'canvas-clear': __('oro.cms.wysiwyg.option_panel.clear_canvas'),
                    'open-tm': __('oro.cms.wysiwyg.option_panel.settings'),
                    'open-sm': __('oro.cms.wysiwyg.option_panel.open_style_manager'),
                    'open-layers': __('oro.cms.wysiwyg.option_panel.open_layer_manager'),
                    'open-blocks': __('oro.cms.wysiwyg.option_panel.open_Blocks')
                }
            }
        },
        selectorManager: {
            label: __('oro.cms.wysiwyg.selector_manager.label'),
            selected: __('oro.cms.wysiwyg.selector_manager.selected'),
            emptyState: __('oro.cms.wysiwyg.selector_manager.empty_state'),
            states: {
                'hover': __('oro.cms.wysiwyg.selector_manager.states.hover'),
                'active': __('oro.cms.wysiwyg.selector_manager.states.active'),
                'nth-of-type(2n)': __('oro.cms.wysiwyg.selector_manager.states.even_odd')
            }
        },
        styleManager: {
            empty: __('oro.cms.wysiwyg.style_manager.empty'),
            layer: __('oro.cms.wysiwyg.style_manager.layer'),
            fileButton: __('oro.cms.wysiwyg.style_manager.file_button'),
            sectors: {
                general: __('oro.cms.wysiwyg.style_manager.sectors.general'),
                layout: __('oro.cms.wysiwyg.style_manager.sectors.layout'),
                typography: __('oro.cms.wysiwyg.style_manager.sectors.typography'),
                decorations: __('oro.cms.wysiwyg.style_manager.sectors.decorations'),
                extra: __('oro.cms.wysiwyg.style_manager.sectors.extra'),
                flex: __('oro.cms.wysiwyg.style_manager.sectors.flex'),
                dimension: __('oro.cms.wysiwyg.style_manager.sectors.dimension')
            }
        },
        traitManager: {
            empty: __('oro.cms.wysiwyg.trait_manager.empty'),
            label: __('oro.cms.wysiwyg.trait_manager.label'),
            traits: {
                // The core library generates the name by their `name` property
                labels: {
                    id: __('oro.cms.wysiwyg.trait_manager.traits.id.label'),
                    alt: __('oro.cms.wysiwyg.trait_manager.traits.alt.label'),
                    title: __('oro.cms.wysiwyg.trait_manager.traits.title.label'),
                    href: __('oro.cms.wysiwyg.trait_manager.traits.href.label'),
                    target: __('oro.cms.wysiwyg.trait_manager.traits.target.label'),
                    provider: __('oro.cms.wysiwyg.trait_manager.traits.provider.label'),
                    src: __('oro.cms.wysiwyg.trait_manager.traits.src.label'),
                    poster: __('oro.cms.wysiwyg.trait_manager.traits.poster.label'),
                    videoId: __('oro.cms.wysiwyg.trait_manager.traits.videoId.label'),
                    rel: __('oro.cms.wysiwyg.trait_manager.traits.rel.label'),
                    modestbranding: __('oro.cms.wysiwyg.trait_manager.traits.modestbranding.label'),
                    color: __('oro.cms.wysiwyg.trait_manager.traits.color.label'),
                    autoplay: __('oro.cms.wysiwyg.trait_manager.traits.autoplay.label'),
                    loop: __('oro.cms.wysiwyg.trait_manager.traits.loop.label'),
                    controls: __('oro.cms.wysiwyg.trait_manager.traits.controls.label')
                },
                title: {
                    autoplay: __('oro.cms.wysiwyg.trait_manager.traits.autoplay.title')
                },
                // In a simple trait, like text input, these are used on input attributes
                attributes: {
                    id: {
                        placeholder: __('oro.cms.wysiwyg.trait_manager.traits.id.placeholder')
                    },
                    alt: {
                        placeholder: __('oro.cms.wysiwyg.trait_manager.traits.alt.placeholder')
                    },
                    title: {
                        placeholder: __('oro.cms.wysiwyg.trait_manager.traits.title.placeholder')
                    },
                    href: {
                        placeholder: __('oro.cms.wysiwyg.trait_manager.traits.href.placeholder')
                    },
                    src: {
                        placeholder: __('oro.cms.wysiwyg.trait_manager.traits.src.placeholder')
                    },
                    poster: {
                        placeholder: __('oro.cms.wysiwyg.trait_manager.traits.poster.placeholder')
                    },
                    videoId: {
                        placeholder: __('oro.cms.wysiwyg.trait_manager.traits.videoId.placeholder')
                    },
                    color: {
                        placeholder: __('oro.cms.wysiwyg.trait_manager.traits.color.placeholder')
                    }
                },
                // In a trait like select, these are used to translate option names
                options: {
                    target: {
                        _self: __('oro.cms.wysiwyg.trait_manager.traits.options.target.this_window'),
                        _blank: __('oro.cms.wysiwyg.trait_manager.traits.options.target.blank')
                    }
                }
            }
        }
    }
};

export default editor => {
    const i18n = editor.I18n;

    if (i18n.getLocale() === locale) {
        i18n.setMessages($.extend(true, {}, i18n.getMessages(), messages));
    } else {
        i18n.setMessages(messages);
        i18n.setLocale(locale);
    }
};
