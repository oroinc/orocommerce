import {isMatch, omit} from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import PictureSettingsDialog from 'orocms/js/app/grapesjs/dialogs/picture-settings-dialog';

const PictureType = BaseType.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.digital_asset.image'),
        category: 'Basic',
        activate: true,
        attributes: {
            'class': 'fa fa-picture-o'
        },
        order: 50
    },

    editorEvents: {
        'component:drag:end': 'onDragEnd'
    },

    commands: {
        'open-picture-settings': (editor, sender, {setSources, setMain, ...props}) => {
            const dialog = new PictureSettingsDialog({
                props,
                editor,
                autoRender: true
            });

            dialog.show();

            dialog.on('saveSources', () => {
                const {sources, main} = dialog.getSources();
                setSources(sources);
                setMain(main);
                dialog.hide();
            });
        }
    },

    modelProps: {
        defaults: {
            tagName: 'picture',
            type: 'picture',
            sources: [],
            editable: true,
            droppable: false
        },

        init() {
            const components = this.get('components');
            if (!this.findType('image').length) {
                components.add({
                    type: 'image',
                    attributes: {
                        src: '#',
                        alt: 'no-alt'
                    }
                });
            }

            this.image = this.findType('image')[0];
            this.image.wrapper = this;

            this.updateToolbar();

            this.listenTo(this.image, 'change:previewMetadata', this.onImageUpdate);
            this.listenTo(this, 'change:sources', this.updateSources);
        },

        getCommandProps() {
            return {
                sources: this.get('sources'),
                mainImage: this.image.toJSON(),
                setSources(sources) {
                    this.set('sources', sources);
                    this.updateSources();
                },
                setMain({attributes}) {
                    this.image.set('src', attributes.src);
                }
            };
        },

        updateToolbar() {
            if (!this.get('toolbar').find(toolbar => toolbar.id === 'picture-settings')) {
                const toolbarAction = {
                    id: 'picture-settings',
                    attributes: {
                        'class': 'fa fa-gear',
                        'label': __('oro.cms.wysiwyg.toolbar.pictureSettings')
                    },
                    command(editor) {
                        let selected = editor.getSelected();
                        if (selected.is('image')) {
                            selected = selected.wrapper;
                        }

                        const {sources, mainImage, setSources, setMain} = selected.getCommandProps();

                        if (mainImage.isNew) {
                            return;
                        }

                        editor.Commands.run('open-picture-settings', {
                            sources,
                            mainImage,
                            setSources: setSources.bind(selected),
                            setMain: setMain.bind(selected)
                        });
                    }
                };

                this.set('toolbar', [
                    toolbarAction,
                    ...this.get('toolbar')
                ]);

                const imageActions = this.image.get('toolbar').map(action => {
                    if (action.command === 'tlb-clone') {
                        action.command = editor => {
                            const selected = editor.getSelected();
                            editor.select(selected.wrapper);
                            editor.runCommand('tlb-clone');
                        };
                        action.id = 'tlb-clone';
                    }

                    return action;
                });

                this.image.set('toolbar', [
                    toolbarAction,
                    ...imageActions
                ]);
            }
        },

        setSource(key, props) {
            let sources = this.get('sources');
            const findIndex = sources.findIndex(({key: sourceKey, attributes}) => {
                if (!sourceKey) {
                    return isMatch(omit(attributes, 'srcset'), omit(props, 'srcset'));
                }
                return sourceKey === key;
            });

            if (findIndex !== -1) {
                sources[findIndex] = {key, attributes: props};
            } else {
                sources = [
                    ...sources,
                    {key, attributes: props}
                ];
            }

            this.set('sources', sources);
            this.trigger('change:sources');
        },

        onImageUpdate(image, {url_webp: srcset}) {
            this.setSource('webp', {
                srcset,
                type: 'image/webp'
            });
        },

        updateSources() {
            this.findType('source').forEach(source => source.remove());
            this.append(this.get('sources').map(({key, attributes}) => {
                return {
                    type: 'source',
                    attributes,
                    key
                };
            }), {
                at: 0
            });
        }
    },

    viewProps: {
        onActive(e) {
            setTimeout(() => {
                this.model.image.view.onActive(e);
            }, 200);
        },
        onRender() {
            // Need for correct work picture tag wrapper with resize and selection
            this.$el.css('display', 'inline-block');
        }
    },

    constructor: function PictureTypeBuilder(options) {
        PictureTypeBuilder.__super__.constructor.call(this, options);
    },

    onDragEnd({target, parent, index}) {
        if (target.is('image') && target.wrapper && parent !== target.wrapper) {
            parent.append(target.wrapper, {
                at: index
            });
            target.move(target.wrapper);
        }
    },

    isComponent(el) {
        if (el.nodeType === Node.ELEMENT_NODE && el.tagName.toLowerCase() === 'picture') {
            return {
                type: this.componentType,
                sources: [...el.querySelectorAll('source')].map(source => {
                    return {
                        attributes: [...source.attributes].reduce((attrs, attribute) => {
                            attrs[attribute.name] = attribute.value;
                            return attrs;
                        }, {})
                    };
                })
            };
        }
    }
}, {
    type: 'picture'
});

export default PictureType;
